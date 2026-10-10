<?php

namespace Bluem\Wordpress\Payments;

final class BluemOrderQuery
{
    /**
     * Build a native WooCommerce metadata equality query for the active order
     * datastore. The legacy CPT datastore uses meta_key/meta_value, whereas
     * HPOS requires meta_query.
     */
    public static function metadataEquals(string $key, string $value): array
    {
        if (
            class_exists('Automattic\\WooCommerce\\Utilities\\OrderUtil')
            && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()
        ) {
            return [
                'meta_query' => [
                    [
                        'key' => $key,
                        'value' => $value,
                    ],
                ],
            ];
        }

        return [
            'meta_key' => $key,
            'meta_value' => $value,
        ];
    }

    /**
     * Translate Bluem's legacy custom order arguments into HPOS metadata
     * queries. The legacy CPT datastore continues to use its existing hook.
     */
    public static function mapHposArgs(array $query_args): array
    {
        // WC_Order_Query applies this filter for both order data stores. The
        // legacy CPT store relies on the custom arguments below being present
        // so its `woocommerce_order_data_store_cpt_get_orders_query` filters
        // can translate them. Removing them there makes the query unfiltered,
        // which can incorrectly return the newest order.
        if (
            ! class_exists('Automattic\\WooCommerce\\Utilities\\OrderUtil')
            || ! \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()
        ) {
            return $query_args;
        }

        $metadata = [
            'bluem_transactionid' => 'bluem_transactionid',
            'bluem_entrancecode' => 'bluem_entrancecode',
            'bluem_mandateid' => 'bluem_mandateid',
        ];

        foreach ($metadata as $argument => $key) {
            if (empty($query_args[$argument])) {
                continue;
            }

            $query_args['meta_query'] = $query_args['meta_query'] ?? [];
            $query_args['meta_query'][] = [
                'key' => $key,
                'value' => esc_attr($query_args[$argument]),
            ];
            unset($query_args[$argument]);
        }

        return $query_args;
    }
}
