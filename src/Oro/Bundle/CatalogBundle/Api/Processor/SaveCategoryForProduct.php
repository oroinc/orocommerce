<?php

namespace Oro\Bundle\CatalogBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class SaveCategoryForProduct implements ProcessorInterface
{
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
    public function process(ContextInterface $context)
    {
        $category = $context->get(RemoveCategoryFromProductRequest::CATEGORY);
        if (null === $category) {
            return;
        }

        // remove current category from product if exists
        $product = $context->getResult();
        $currentCategory = $this->doctrineHelper
            ->getEntityRepository(Category::class)
            ->findOneByProductSku($product->getSku());

        if ($currentCategory instanceof Category) {
            $currentCategory->removeProduct($product);
            // need to flush before adding setting new category
            $this->doctrineHelper->getEntityManager(Category::class)->flush();
        }

        $category->addProduct($product);
        $this->doctrineHelper->getEntityManager(Category::class)->flush();
        $context->remove(RemoveCategoryFromProductRequest::CATEGORY);
    }
}
