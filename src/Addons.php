<?php

/*
 * Copyright (C) 2017  Julian Maurice
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OmekaAddonsIndex;

use DateTime;
use ZipArchive;
use Goutte\Client;

abstract class Addons
{
    protected $url;
    protected $iniFilename;

    public function update($filename)
    {
        $oldAddons = [];
        $addonsByUrl = [];

        if (file_exists($filename)) {
            $oldAddons = json_decode(file_get_contents($filename), true);

            foreach ($oldAddons as $addonDirName => $addon) {
                foreach ($addon['versions'] as &$version) {
                    $addonsByUrl[$version['url']] = [
                        'name' => $addonDirName,
                        'version' => $version,
                    ];
                }
            }
        }

        $client = new Client();
        $root = $client->request('GET', $this->url);
        $downloadLinks = $root
            ->filter('.download a');

        $addons = [];
        for ($i = 0; $i < count($downloadLinks); ++$i) {
            $downloadLink = $downloadLinks->eq($i);
            $addonLink = $this->getAddonLinkFromDownloadLink($downloadLink);

            $page = $client->request('GET', $addonLink->link()->getUri());
            $trs = $page->filter('table.versions tbody tr');
            for ($j = 0; $j < count($trs); ++$j){
                $tr = $trs->eq($j);
                $url = $tr->filter('td')->eq(0)->filter('a')->link()->getUri();

                if (array_key_exists($url, $addonsByUrl)) {
                    fwrite(STDERR, "Skipping $url\n");

                    $oldPlugin = $addonsByUrl[$url];
                    $addonDirName = $oldPlugin['name'];
                    $addonVersion = &$oldPlugin['version'];
                } else {
                    fwrite(STDERR, "Downloading $url...\n");
                    $fh = fopen($url, 'r');
                    if ($fh === false) {
                        fwrite(STDERR, "Skipping $url because of failed download\n");
                        continue;
                    }

                    $zip = tempnam(sys_get_temp_dir(), 'omeka-addon.');
                    file_put_contents($zip, $fh);

                    // Get addon's directory name and contents of addon.ini
                    $zipArchive = new ZipArchive();
                    $zipArchive->open($zip);
                    $addonDirName = $this->getAddonDirName($zipArchive);
                    $topLevelDirName = $this->getTopLevelDirName($zipArchive);

                    $iniPath = $topLevelDirName . '/' . $this->iniFilename;
                    $ini = $zipArchive->getFromName($iniPath);
                    $zipArchive->close();
                    unlink($zip);

                    // Release date
                    $date = $tr->filter('td')->eq(1)->text();
                    $date = preg_replace('/\[info\]/', '', $date);
                    $dt = new DateTime($date);

                    $info = parse_ini_string($ini);
                    if ($info !== false) {
                        $addonVersion = [
                            'url' => $url,
                            'date' => $dt->format('Y-m-d'),
                            'info' => $info,
                        ];
                    } else {
                        $addonVersion = null;
                    }
                }

                if (isset($addonVersion)) {
                    $addons[$addonDirName]['versions'][] = $addonVersion;
                }
            }
        }

        ksort($addons);

        file_put_contents($filename, json_encode($addons, JSON_PRETTY_PRINT));
    }

    protected function getAddonDirName($zipArchive)
    {
        return $this->getTopLevelDirName($zipArchive);
    }

    protected function getTopLevelDirName($zipArchive)
    {
        $name = $zipArchive->getNameIndex(0);
        $dirname = dirname($name);
        $topLevelDirName = ($dirname === '.') ? $name : $dirname;
        $topLevelDirName = rtrim($topLevelDirName, '/');

        return $topLevelDirName;
    }

    abstract protected function getAddonLinkFromDownloadLink($downloadLink);
}
