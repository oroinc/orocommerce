<?php

namespace Oro\Bundle\WebsiteSearchBundle\Helper;

use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderDecorator;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderInterface;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderRegistry;

/**
 * Handy methods for working with search placeholders.
 */
class PlaceholderHelper
{
    private PlaceholderRegistry $placeholderRegistry;

    private AbstractSearchMappingProvider $searchMappingProvider;

    public function __construct(
        PlaceholderRegistry $placeholderRegistry,
        AbstractSearchMappingProvider $searchMappingProvider
    ) {
        $this->placeholderRegistry = $placeholderRegistry;
        $this->searchMappingProvider = $searchMappingProvider;
    }

    /**
     * Tells if the given string with resolved placeholder satisfies the string with unresolved placeholder.
     *
     * @param string $name For example, "oro_product_WEBSITE_ID"
     * @param string $nameValue For example, "oro_product_1"
     * @return bool
     */
    public function isNameMatch($name, $nameValue): bool
    {
        $placeholderNames = [];
        $placeholderPatterns = [];
        $withPlaceholders = false;

        // quick check because placeholders are always uppercase
        if (!preg_match('/[A-Z]+/', $name)) {
            return false;
        }

        foreach ($this->placeholderRegistry->getPlaceholders() as $placeholder) {
            $placeholderToken = $placeholder->getPlaceholder();
            $placeholderNames[] = $placeholderToken;
            $placeholderPatterns[] = PlaceholderDecorator::DEFAULT_PLACEHOLDER_VALUE;

            if (str_contains($name, $placeholderToken)) {
                $withPlaceholders = true;
            }
        }

        if (!$withPlaceholders) {
            return false;
        }

        if (str_contains($name, '.')) {
            $parts = explode('.', $name);
            $aliasPattern = $parts[0];
        } else {
            $aliasPattern = str_replace($placeholderNames, $placeholderPatterns, $name);
        }

        return preg_match('/^' . $aliasPattern . '/', $nameValue);
    }

    /**
     * Returns entity class for the specified index alias.
     *
     * @param string $indexAlias For example, oro_product_1
     * @return string
     */
    public function getEntityClassByResolvedIndexAlias(string $indexAlias): string
    {
        $entityClass = '';
        $entityClassListAliases = $this->searchMappingProvider->getEntitiesListAliases();
        foreach ($entityClassListAliases as $className => $alias) {
            if ($this->isNameMatch($alias, $indexAlias)) {
                $entityClass = $className;
                break;
            }
        }

        return $entityClass;
    }

    /**
     * @return string[]
     */
    public function getPlaceholderKeys(): array
    {
        return array_map(function (PlaceholderInterface $placeholder) {
            return $placeholder->getPlaceholder();
        }, $this->placeholderRegistry->getPlaceholders());
    }
}
