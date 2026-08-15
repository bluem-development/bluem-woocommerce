#!/usr/bin/env bash

set -euo pipefail

acceptance_url="${WP_ACCEPTANCE_URL:-http://localhost:8000}"
admin_user="${WP_ACCEPTANCE_ADMIN_USER:-wordpress}"
admin_password="${WP_ACCEPTANCE_ADMIN_PASSWORD:-wordpress}"
admin_email="${WP_ACCEPTANCE_ADMIN_EMAIL:-wordpress@example.com}"

wpcli() {
    docker compose run --rm wpcli --allow-root "$@"
}

installed=0
for attempt in $(seq 1 30); do
    if wpcli db check --skip-ssl >/dev/null 2>&1; then
        if wpcli core is-installed >/dev/null 2>&1; then
            installed=1
            break
        fi

        if wpcli core install \
            --url="$acceptance_url" \
            --title="Bluem Acceptance Test" \
            --admin_user="$admin_user" \
            --admin_password="$admin_password" \
            --admin_email="$admin_email" \
            --skip-email >/dev/null 2>&1; then
            installed=1
            break
        fi
    fi

    if [ "$attempt" -eq 30 ]; then
        echo "WordPress did not become ready for acceptance tests." >&2
        exit 1
    fi

    sleep 2
done

if [ "$installed" -ne 1 ]; then
    echo "WordPress installation could not be prepared." >&2
    exit 1
fi

wpcli language core install en_US
wpcli site switch-language en_US
wpcli language core install nl_NL
# Bluem's WooCommerce callback endpoints use pretty URLs under /wc-api/.
# A fresh WordPress installation defaults to the Plain permalink structure,
# which routes those callback URLs to the front page instead of the gateway.
wpcli rewrite structure '/%postname%/' --hard
wpcli plugin activate bluem
wpcli plugin is-active bluem

# Complete the plugin's setup guard with isolated, non-production values so
# acceptance tests do not require manual activation-form submission.
wpcli option update bluem_woocommerce_options '{"environment":"test","senderID":"S0001","test_accessToken":"ci-acceptance-token","production_accessToken":"ci-acceptance-production-token","expectedReturnStatus":"success","suppress_woo":"1","suppress_warning":"1","payments_enabled":"0","mandates_enabled":"0","idin_enabled":"0"}' --format=json
wpcli option update bluem_plugin_registration 1
