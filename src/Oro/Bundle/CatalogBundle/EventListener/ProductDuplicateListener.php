<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent;

class ProductDuplicateListener
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var string
     */
    protected $categoryClass;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function setDoctrineHelper(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param string $categoryClass
     */
    public function setCategoryClass($categoryClass)
    {
        $this->categoryClass = $categoryClass;
    }

    /**
     * Link new product with category from source product
     *
     * @param ProductDuplicateAfterEvent $event
     */
    public function onDuplicateAfter(ProductDuplicateAfterEvent $event)
    {
        $product = $event->getProduct();
        $sourceProduct = $event->getSourceProduct();

        $category = $this->getCategoryRepository()->findOneByProduct($sourceProduct);

        if ($category !== null) {
            $category->addProduct($product);
            $objectManager = $this->doctrineHelper->getEntityManager($this->categoryClass);
            $objectManager->flush();
        }
    }

    /**
     * @return CategoryRepository
     */
    protected function getCategoryRepository()
    {
        return $this->doctrineHelper->getEntityRepository($this->categoryClass);
    }
}
