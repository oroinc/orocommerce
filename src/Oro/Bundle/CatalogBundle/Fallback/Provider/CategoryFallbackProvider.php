<?php

namespace Oro\Bundle\CatalogBundle\Fallback\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\EntityBundle\Fallback\Provider\AbstractEntityFallbackProvider;
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
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
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

        return $categoryRepo->findOneByProduct($object);
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
