<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;

class ProductVariantsGridEventListener
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var ProductRepository
     */
    protected $repository;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @return ProductRepository
     */
    protected function getRepository()
    {
        if (!$this->repository) {
            $this->repository = $this->doctrineHelper->getEntityRepository(Product::class);
        }

        return $this->repository;
    }

    /**
     * Add restriction to only show products that have all variant fields values set
     *
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $parameters = $event->getDatagrid()->getParameters();

        if (!$parameters->has('parentProduct')) {
            return;
        }

        $parentProductId = $parameters->get('parentProduct');

        $parentProduct = $this->getRepository()->find($parentProductId);
        if (!$parentProduct) {
            return;
        }

        $variantFields = $parentProduct->getVariantFields();

        // Don't show any product variants if there are no variant fields specified in the configurable product
        if (!$variantFields) {
            $event->getConfig()->offsetAddToArrayByPath(
                '[source][query][where][and]',
                ['1 = 0']
            );

            return;
        }

        foreach ($variantFields as $variantFieldName) {
            $event->getConfig()->offsetAddToArrayByPath(
                '[source][query][where][and]',
                [sprintf('product.%s is not null', $variantFieldName)]
            );
        }
    }
}
