#!/usr/bin/env sh

unset GIT_AUTHOR_NAME
unset GIT_AUTHOR_EMAIL
unset GIT_AUTHOR_DATE
unset GIT_COMMITTER_NAME
unset GIT_COMMITTER_EMAIL
unset GIT_COMMITTER_DATE

git config --global --add safe.directory /drone/src

git config user.email "drone@biblibre.com"
git config user.name "Drone CI"

git add data
git commit -m "Drone build: $DRONE_BUILD_NUMBER"

mkdir -p ~/.ssh
printenv GH_DEPLOY_KEY > ~/.ssh/deploy_key
chmod 600 ~/.ssh/deploy_key
cat > ~/.ssh/config << 'CONFIG'
Host github.com
User git
IdentityFile ~/.ssh/deploy_key
StrictHostKeyChecking accept-new
CONFIG
git push --quiet git@github.com:biblibre/omeka-addons-index.git HEAD:$DRONE_BRANCH
