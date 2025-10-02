#!/usr/bin/env sh
set -e

REQUIRED=("composer" "npm" "sass" "zip" "wp")

for i in ${REQUIRED[@]}
do
  if ! [ -x "$(command -v $i)" ]; then
    echo "Error: ${i} is not installed." >&2
    exit 1
  fi
done

DIST_DIR="_dist"
PLUGIN_SLUG="woo-payu-payment-gateway"
DIST_PLUGIN=${DIST_DIR}/${PLUGIN_SLUG}

# Clean
rm -rf $DIST_DIR

# Composer
composer install --no-dev --optimize-autoloader

# SASS
sass ./assets/css:./assets/css --no-source-map --style=compressed

# Build BLocks
npm run build

# Blocks translates
rm ./lang/*.json
wp i18n make-json lang --no-purge --use-map=blocks_translates_map.json

# Create folder
mkdir -p ${DIST_PLUGIN}

# Copy
mkdir -p ${DIST_PLUGIN}/assets/css/
cp -R assets/css/*.css ${DIST_PLUGIN}/assets/css/
cp -R assets/js ${DIST_PLUGIN}/assets/
cp -R assets/images ${DIST_PLUGIN}/assets/
cp -R build ${DIST_PLUGIN}
mkdir ${DIST_PLUGIN}/lang/
cp lang/${PLUGIN_SLUG}*.{json,php,po,mo,pot} ${DIST_PLUGIN}/lang/
cp -R Payu ${DIST_PLUGIN}
cp -R vendor ${DIST_PLUGIN}
cp -R templates ${DIST_PLUGIN}
cp changelog.txt ${DIST_PLUGIN}
cp readme.txt ${DIST_PLUGIN}
cp woocommerce-gateway-payu.php ${DIST_PLUGIN}

# Compress
(cd ${DIST_DIR}; zip -r ${PLUGIN_SLUG}.zip ${PLUGIN_SLUG};)
