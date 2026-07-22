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
    if wpcli db check >/dev/null 2>&1; then
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

wpcli plugin activate bluem
wpcli plugin is-active bluem
