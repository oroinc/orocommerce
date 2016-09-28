<?php

namespace Oro\Bundle\WebsiteSearchBundle\Helper;

class FieldHelper
{
    /**
     * @param string $text
     * @return string
     */
    public function stripTagsAndSpaces($text)
    {
        $stripTagsWithExcessiveSpaces = html_entity_decode(
            strip_tags(
                str_replace('>', '> ', $text)
            )
        );

        return trim(
            preg_replace('/\s+/u', ' ', $stripTagsWithExcessiveSpaces)
        );
    }
}
