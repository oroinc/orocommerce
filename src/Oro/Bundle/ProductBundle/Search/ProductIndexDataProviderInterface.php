<?php

namespace Oro\Bundle\ProductBundle\Search;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Returns product information to store at website search index
 */
interface ProductIndexDataProviderInterface
{
    /**
     * Get website search index information for the specified product, attribute and localizations
     *
     * @param Product $product
     * @param FieldConfigModel $attribute
     * @param array|Localization[] $localizations
     * @return \ArrayIterator|ProductIndexDataModel[]
     */
    public function getIndexData(Product $product, FieldConfigModel $attribute, array $localizations): \ArrayIterator;
}
