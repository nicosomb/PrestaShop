#!/bin/bash

###
# This script rebuilds all the static assets, running npm install-clean as needed
#

#http://redsymbol.net/articles/unofficial-bash-strict-mode/
set -euo pipefail
PROJECT_PATH=$(cd "$( dirname "$0" )/../../" && pwd)
ADMIN_DIR="${PROJECT_PATH}/${ADMIN_DIR:-admin-dev}"

if [[ ! -d $ADMIN_DIR ]]; then
  echo "Could not find directory '$ADMIN_DIR'. Make sure to launch this script from the root directory of PrestaShop"
  return 1
fi

function build {
  if [[ -z "$1" ]]; then
    echo "Parameter is empty"
    exit 1
  fi
  if [[ ! -d $1 ]]; then
     echo $1 folder not found
     exit 1
  fi

  pushd $1
  if [[ -d "node_modules" ]]; then
    rm -rf node_modules
  fi

  touch buildLock
  chmod 664 buildLock
  npm ci
  npm run build
  rm buildLock
  popd
}

build_asset() {
  case $1 in
    admin-default)
      echo ">>> Building admin default theme..."
      build "$ADMIN_DIR/themes/default"
    ;;
    admin-new-theme)
      echo ">>> Building admin new theme..."
      build "$ADMIN_DIR/themes/new-theme"
    ;;
    front-core)
      echo ">>> Building core theme assets..."
      build "$PROJECT_PATH/themes"
    ;;
    front-classic)
      echo ">>> Building classic theme assets..."
      build "$PROJECT_PATH/themes/classic/_dev"
    ;;
    front-hummingbird)
      echo ">>> Building hummingbird theme assets..."
      build "$PROJECT_PATH/themes/hummingbird"
    ;;
    all)
      build_asset admin-default & build_asset admin-new-theme & build_asset front-core & build_asset front-classic & build_asset front-hummingbird
    ;;
    *)
      echo "Unknown asset to build $1"
    ;;
  esac
}

if test $# -gt 0; then
  build_asset $1
else
  build_asset all
fi

wait
echo "All done!"
