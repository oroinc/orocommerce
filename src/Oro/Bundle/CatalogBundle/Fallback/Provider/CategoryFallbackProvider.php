<?php

namespace Oro\Bundle\CatalogBundle\Fallback\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\EntityBundle\Fallback\Provider\AbstractEntityFallbackProvider;
use Oro\Bundle\EntityBundle\Fallback\Provider\SystemConfigFallbackProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Exception\Fallback\InvalidFallbackArgumentException;
use Oro\Bundle\ProductBundle\Entity\Product;

class CategoryFallbackProvider extends AbstractEntityFallbackProvider
{
    const FALLBACK_ID = 'category';

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var SystemConfigFallbackProvider
     */
    protected $systemConfigFallbackProvider;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        SystemConfigFallbackProvider $systemConfigFallbackProvider
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->systemConfigFallbackProvider = $systemConfigFallbackProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getFallbackHolderEntity($object, $objectFieldName)
    {
        if (!$object instanceof Product) {
            throw new InvalidFallbackArgumentException(get_class($object), get_class($this));
        }
        /** @var CategoryRepository $categoryRepo */
        $categoryRepo = $this->doctrineHelper->getEntityRepository(Category::class);
        $category = $categoryRepo->findOneByProduct($object);
        return $category ?: $this->systemConfigFallbackProvider->getFallbackHolderEntity($object, $objectFieldName);
    }

    /**
     * {@inheritdoc}
     */
    public function isFallbackSupported($object, $objectFieldName)
    {
        return $object instanceof Product;
    }

    /**
     * {@inheritdoc}
     */
    public function getFallbackLabel()
    {
        return 'oro.catalog.fallback.category.label';
    }
}
