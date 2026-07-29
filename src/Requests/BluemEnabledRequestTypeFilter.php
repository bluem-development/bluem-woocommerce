<?php

namespace Bluem\Wordpress\Requests;

use Closure;

final class BluemEnabledRequestTypeFilter
{
    public function __construct(private readonly Closure $isModuleEnabled)
    {
    }

    /**
     * Keep request types whose corresponding Bluem module is enabled.
     */
    public function filter(array $types): array
    {
        return array_filter($types, function ($type): bool {
            $moduleId = $type === 'identity' ? 'idin' : $type;

            return ($this->isModuleEnabled)($moduleId);
        });
    }
}
