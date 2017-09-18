#!/usr/bin/env sh

git config user.email "travis@travis-ci.org"
git config user.name "Travis CI"

git add data
git commit -m "Travis build: $TRAVIS_BUILD_NUMBER"

git push --quiet https://${GH_TOKEN}@github.com/jajm/omeka-addons-index.git HEAD:$TRAVIS_BRANCH
