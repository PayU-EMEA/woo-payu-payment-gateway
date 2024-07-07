#!/usr/bin/env sh
rm ./lang/*.json
wp i18n make-json lang --no-purge --use-map=blocks_translates_map.json
