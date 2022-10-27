<?php

namespace Oro\Bundle\WebsiteSearchBundle\Placeholder;

/**
 * Replaces all placeholders with regular expression.
 */
class RegexPlaceholderDecorator extends PlaceholderDecorator
{
    public function replaceDefault($string)
    {
        foreach ($this->placeholderRegistry->getPlaceholders() as $placeholder) {
            $string = str_replace(
                $placeholder->getPlaceholder(),
                self::DEFAULT_PLACEHOLDER_VALUE,
                $string
            );
        }

        return $string;
    }
}
