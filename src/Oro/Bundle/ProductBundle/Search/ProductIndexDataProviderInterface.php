<?php

namespace Oro\Bundle\ProductBundle\Search;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Entity\Product;

interface ProductIndexDataProviderInterface
{
    /**
     * @param Product $product
     * @param FieldConfigModel $attribute
     * @param array|Localization[] $localizations
     * @return array|ProductIndexDataModel[]
     */
    public function getIndexData(Product $product, FieldConfigModel $attribute, array $localizations);
}
