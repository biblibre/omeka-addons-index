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

class Plugins extends Addons
{
    protected $url = 'http://omeka.org/add-ons/plugins';
    protected $iniFilename = 'plugin.ini';

    protected function getAddonLinkFromDownloadLink($downloadLink)
    {
        return $downloadLink->parents()->parents()->parents()->filter('h2 > a');
    }

    protected function getAddonDirName($zipArchive)
    {
        for ($i = 0; $i < $zipArchive->numFiles; ++$i) {
            $name = $zipArchive->getNameIndex($i);
            $matches = array();
            if (preg_match('/\/([^\/]+)Plugin.php$/', $name, $matches)) {
                return $matches[1];
            }
        }

        return parent::getAddonDirName($zipArchive);
    }
}
