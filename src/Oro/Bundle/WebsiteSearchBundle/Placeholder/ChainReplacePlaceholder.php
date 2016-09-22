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
     * @param array $placeholdersValues
     * @return string
     */
    public function replace($string, $placeholdersValues)
    {
        foreach ($this->placeholderRegistry->getPlaceholders() as $placeholder) {
            if (isset($placeholdersValues[$placeholder->getPlaceholder()])) {
                $replaceOn = $placeholdersValues[$placeholder->getPlaceholder()];
                $string = $placeholder->replace($string, $replaceOn);
            }
        }

        return $string;
    }
}
