<?php

namespace Bluem\Wordpress\Payments;

final class BluemOrderQuery
{
    /**
     * Translate Bluem's legacy custom order arguments into HPOS metadata
     * queries. The legacy CPT datastore continues to use its existing hook.
     */
    public static function mapHposArgs(array $query_args): array
    {
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
