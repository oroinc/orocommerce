<?php

namespace Oro\Bundle\WebsiteSearchBundle\Placeholder;

class ChainReplacePlaceholder
{
    /**
     * @var WebsiteSearchPlaceholderRegistry
     */
    private $placeholderRegistry;

    /**
     * @param WebsiteSearchPlaceholderRegistry $registry
     */
    public function __construct(WebsiteSearchPlaceholderRegistry $registry)
    {
        $this->placeholderRegistry = $registry;
    }

    /**
     * @param string $string
     * @param string $replaceValue
     * @return string
     */
    public function replace($string, $replaceValue)
    {
        foreach ($this->placeholderRegistry->getPlaceholders() as $placeholder) {
            $string = $placeholder->replace($string, $replaceValue);
        }

        return $string;
    }
}
