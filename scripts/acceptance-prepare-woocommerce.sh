#!/usr/bin/env bash

set -euo pipefail

wpcli() {
    docker compose run --rm wpcli --allow-root "$@"
}

# Keep the WooCommerce version explicit so checkout coverage is reproducible.
woocommerce_version="${WOOCOMMERCE_VERSION:-10.9.4}"
if ! wpcli plugin is-installed woocommerce >/dev/null 2>&1; then
    wpcli plugin install woocommerce --version="$woocommerce_version"
fi
wpcli plugin activate woocommerce
wpcli option update bluem_woocommerce_options '{"environment":"test","senderID":"ci-acceptance-sender","test_accessToken":"ci-acceptance-token","production_accessToken":"ci-acceptance-production-token","expectedReturnStatus":"success","suppress_woo":"1","suppress_warning":"1","payments_enabled":"1","mandates_enabled":"1","idin_enabled":"1"}' --format=json
wpcli option update bluem_plugin_registration 1
wpcli plugin activate bluem
wpcli eval-file /opt/bluem-scripts/acceptance-prepare-fixtures.php
