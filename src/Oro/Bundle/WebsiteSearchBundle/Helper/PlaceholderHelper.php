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
    /** @var PlaceholderRegistry */
    private $placeholderRegistry;

    /** @var AbstractSearchMappingProvider */
    private $searchMappingProvider;

    /**
     * @param PlaceholderRegistry $placeholderRegistry
     * @param AbstractSearchMappingProvider $searchMappingProvider
     */
    public function __construct(PlaceholderRegistry $placeholderRegistry)
    {
        $this->placeholderRegistry = $placeholderRegistry;
    }

    public function setSearchMappingProvider(AbstractSearchMappingProvider $searchMappingProvider): void
    {
        $this->searchMappingProvider = $searchMappingProvider;
    }

    /**
     * Tells if the given string with resolved placeholder satisfies the string with unresolved placeholder.
     *
     * @param string $name For example, "oro_product_WEBSITE_ID"
     * @param string $nameValue For example, "oro_product_1"
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
            $placeholderToken = $placeholder->getPlaceholder();
            $placeholderNames[] = $placeholderToken;
            $placeholderPatterns[] = PlaceholderDecorator::DEFAULT_PLACEHOLDER_VALUE;

            if (strpos($name, $placeholderToken) !== false) {
                $withPlaceholders = true;
            }
        }

        if (!$withPlaceholders) {
            return false;
        }

        $aliasPattern = str_replace($placeholderNames, $placeholderPatterns, $name);

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
        if (!$this->searchMappingProvider) {
            return '';
        }

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
