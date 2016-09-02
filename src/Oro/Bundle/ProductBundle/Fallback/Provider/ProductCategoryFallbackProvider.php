<?php

namespace Oro\Bundle\ProductBundle\Fallback\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\Fallback\Provider\AbstractEntityFallbackProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\EntityBundle\Exception\InvalidFallbackArgumentException;

class ProductCategoryFallbackProvider extends AbstractEntityFallbackProvider
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * ProductCategoryFallbackProvider constructor.
     *
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getFallbackHolderEntity(
        $object,
        $objectFieldName,
        EntityFieldFallbackValue $objectFallbackValue,
        $fallbackConfig
    ) {
        if (!$object instanceof Product) {
            throw new InvalidFallbackArgumentException(get_class($object), get_class($this));
        }

        return $this->doctrineHelper->getEntityRepository(Category::class)->findOneByProduct($object);
    }
}
