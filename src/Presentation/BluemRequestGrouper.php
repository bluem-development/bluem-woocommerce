<?php

namespace Bluem\Wordpress\Presentation;

final class BluemRequestGrouper
{
    /**
     * Group request records into the enabled request-type buckets.
     *
     * The legacy `payments` type is presented in the `ideal` bucket for
     * compatibility with the existing admin screen.
     *
     * @param array<int, object> $requests
     * @param array<int, string> $enabledTypes
     * @return array<string, array<int, object>>
     */
    public function group(array $requests, array $enabledTypes): array
    {
        $groupedRequests = [];
        foreach ($enabledTypes as $type) {
            $groupedRequests[$type] = [];
        }

        foreach ($requests as $request) {
            $type = $request->type === 'payments' ? 'ideal' : $request->type;
            $groupedRequests[$type][] = $request;
        }

        return $groupedRequests;
    }
}
