#!/usr/bin/env bash

set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
repo_dir="$(cd "${script_dir}/.." && pwd)"
compose_file="${repo_dir}/docker-compose.integration.yml"
woocommerce_version="${WOOCOMMERCE_VERSION:-10.9.4}"
current_project=""

compose() {
    docker compose --project-name "${current_project}" --file "${compose_file}" "$@"
}

cleanup() {
    local exit_code=$?

    if [[ -n "${current_project}" ]]; then
        if (( exit_code != 0 )); then
            compose logs --no-color wordpress db || true
        fi
        compose down --volumes --remove-orphans >/dev/null 2>&1 || true
    fi

    return "${exit_code}"
}

trap cleanup EXIT

wait_for_wordpress() {
    local attempts=0

    until curl --silent --show-error --fail --location --max-time 5 http://localhost:8001/wp-login.php >/dev/null 2>&1; do
        attempts=$((attempts + 1))
        if (( attempts >= 60 )); then
            echo "WordPress did not become ready on http://localhost:8001."
            compose logs wordpress db
            exit 1
        fi
        sleep 2
    done
}

wp() {
    compose run --rm --no-deps wpcli --allow-root "$@"
}

prepare_site() {
    compose up --detach db wordpress
    wait_for_wordpress

    if ! wp core is-installed >/dev/null 2>&1; then
        wp core install \
            --url=http://localhost:8001 \
            --title="Bluem integration test" \
            --admin_user=wordpress \
            --admin_password=wordpress \
            --admin_email=wordpress@example.test
    fi

    wp plugin install woocommerce --version="${woocommerce_version}" --activate --force
    wp plugin activate bluem-woocommerce
    wp option update permalink_structure '/%postname%/'
}

for hpos_mode in enabled disabled; do
    current_project="bluem-woocommerce-hpos-${hpos_mode}"
    compose down --volumes --remove-orphans >/dev/null 2>&1 || true
    prepare_site

    if [[ "${hpos_mode}" == "enabled" ]]; then
        wp wc hpos enable
    else
        wp wc hpos disable
    fi

    echo "Running order-storage integration test with HPOS ${hpos_mode}..."
    wp eval-file /var/www/html/wp-content/plugins/bluem-woocommerce/tests/Integration/order-storage-test.php
    compose down --volumes --remove-orphans
done

echo "HPOS and legacy order-storage integration tests passed."
