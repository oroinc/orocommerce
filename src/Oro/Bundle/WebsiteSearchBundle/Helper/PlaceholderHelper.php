<?php

namespace Oro\Bundle\WebsiteSearchBundle\Helper;

use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderDecorator;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderRegistry;

class PlaceholderHelper
{
    /** @var PlaceholderRegistry */
    private $placeholderRegistry;

    /**
     * @param PlaceholderRegistry $placeholderRegistry
     */
    public function __construct(PlaceholderRegistry $placeholderRegistry)
    {
        $this->placeholderRegistry = $placeholderRegistry;
    }

    /**
     * @param string $name
     * @param string $nameValue
     * @return bool
     */
    public function isNameMatch($name, $nameValue)
    {
        $placeholderNames = [];
        $placeholderPatterns = [];
        $withPlaceholders = false;

        // quick check because placeholders are always uppercase
        if (!preg_match('/[A-Z]+/', $name)) {
            return false;
        }

        foreach ($this->placeholderRegistry->getPlaceholders() as $placeholder) {
            $placeholderNames[] = $placeholder->getPlaceholder();
            $placeholderPatterns[] = PlaceholderDecorator::DEFAULT_PLACEHOLDER_VALUE;

            if (strpos($name, $placeholder->getPlaceholder()) !== false) {
                $withPlaceholders = true;
            }
        }

        if (!$withPlaceholders) {
            return false;
        }

        $aliasPattern = str_replace($placeholderNames, $placeholderPatterns, $name);

        return preg_match('/' . $aliasPattern . '/', $nameValue);
    }
}
