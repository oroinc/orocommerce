<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;

class ProductVariantsGridEventListener
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ProductRepository */
    protected $repository;

    /** @var string */
    protected $productClass;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param string $productClass
     */
    public function __construct(DoctrineHelper $doctrineHelper, $productClass)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->productClass = $productClass;
    }

    /**
     * @return ProductRepository
     */
    protected function getRepository()
    {
        if (!$this->repository) {
            $this->repository = $this->doctrineHelper->getEntityRepository($this->productClass);
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

        /** @var Product $parentProduct */
        $parentProduct = $this->getRepository()->find($parentProductId);
        if (!$parentProduct) {
            return;
        }

        $wherePath = '[source][query][where][and]';
        $config = $event->getConfig();
        $variantFields = $parentProduct->getVariantFields();

        // Don't show any product variants if there are no variant fields specified in the configurable product
        if (!$variantFields) {
            $config->offsetAddToArrayByPath($wherePath, ['1 = 0']);

            return;
        }

        $from = $config->offsetGetByPath('[source][query][from]', []);
        $from = reset($from);

        if (false === $from) {
            return;
        }

        $rootEntityAlias = $from['alias'];

        $variantWherePart = [];
        foreach ($variantFields as $variantFieldName) {
            $variantWherePart[] = sprintf('%s.%s is not null', $rootEntityAlias, $variantFieldName);
        }

        $config->offsetAddToArrayByPath($wherePath, $variantWherePart);
    }
}
