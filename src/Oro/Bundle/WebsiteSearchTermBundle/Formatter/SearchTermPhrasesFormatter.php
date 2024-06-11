<?php

namespace Oro\Bundle\WebsiteSearchTermBundle\Formatter;

/**
 * Formats Search Term phrases string.
 */
class SearchTermPhrasesFormatter
{
    public function __construct(private string $delimiter)
    {
    }

    public function formatPhrasesToArray(string $phrases): array
    {
        if ($phrases === '') {
            return [];
        }

        return explode($this->delimiter, $phrases);
    }
}
