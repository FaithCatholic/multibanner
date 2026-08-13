#!/bin/bash
set -e -o pipefail

./.ddev/commands/web/poser

# If db empty, do initial setup
if [ -z "$(drush status --field=bootstrap)" ]; then
  drush si --site-name=multibanner --account-pass=1 -y
  # Core's Navigation module supersedes Toolbar. For now our local development
  # preference continues to be admin toolbar
  if drush pm:list --status=enabled --format=list | grep -qx navigation; then
    drush pm:uninstall -y navigation
  fi
  drush en -y devel admin_toolbar admin_toolbar_tools
  drush en -y multibanner

  drush rap authenticated 'access devel information'
fi

# Always output this
drush uli
