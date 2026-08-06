<?php

namespace Bluem\Wordpress\Support;

final class BluemSupportReportTrace
{
    /**
     * Remove the collector frame and retain only safe, compact frame fields.
     *
     * @param array<int, array<string, mixed>> $trace
     * @return array<int, array{function: string, file: string, line: int|string}>
     */
    public function format(array $trace): array
    {
        return array_map(
            static function (array $frame): array {
                return [
                    'function' => ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? ''),
                    'file' => $frame['file'] ?? '',
                    'line' => $frame['line'] ?? '',
                ];
            },
            array_slice($trace, 1)
        );
    }
}
