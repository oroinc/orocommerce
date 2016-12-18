<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration as FormatterConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Sorter\Configuration as SorterConfiguration;
use Oro\Bundle\FilterBundle\Grid\Extension\Configuration as FilterConfiguration;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\CustomFieldProvider;

class ProductVariantCustomFieldsDatagridListener
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var CustomFieldProvider
     */
    private $customFieldProvider;

    /**
     * @var string
     */
    private $productClass;

    /**
     * @var string
     */
    private $productVariantLinkClass;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param CustomFieldProvider $customFieldProvider
     * @param string $productClass
     * @param string $productVariantLinkClass
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        CustomFieldProvider $customFieldProvider,
        $productClass,
        $productVariantLinkClass
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->customFieldProvider = $customFieldProvider;
        $this->productClass = $productClass;
        $this->productVariantLinkClass = $productVariantLinkClass;
    }

    /**
     * Add restriction to show only products that have all variant fields values set
     *
     * @param BuildBefore $event
     */
    public function onBuildBeforeHideUnsuitable(BuildBefore $event)
    {
        $parameters = $event->getDatagrid()->getParameters();

        if (!$parameters->has('parentProduct')) {
            return;
        }

        $parentProductId = $parameters->get('parentProduct');

        /** @var Product $parentProduct */
        $parentProduct = $this->getProductRepository()->find($parentProductId);
        if (!$parentProduct) {
            return;
        }

        $andWherePath = '[source][query][where][and]';
        $orWherePath = '[source][query][where][or]';
        $config = $event->getConfig();
        $variantFields = $parentProduct->getVariantFields();

        // Don't show any product variants if there are no variant fields specified in the configurable product
        if (!$variantFields) {
            $config->offsetAddToArrayByPath($andWherePath, ['1 = 0']);

            return;
        }

        $from = $this->getFrom($config);
        $rootEntityAlias = $from['alias'];

        $variantAndWherePart = [];
        foreach ($variantFields as $variantFieldName) {
            $variantAndWherePart[] = sprintf('%s.%s IS NOT NULL', $rootEntityAlias, $variantFieldName);
        }

        $config->offsetAddToArrayByPath($andWherePath, $variantAndWherePart);

        // Show all linked variants
        $variantLinkLeftJoin = $this->getVariantLinkLeftJoin($config);
        $variantLinkAlias = $variantLinkLeftJoin['alias'];

        $variantOrWherePart = [
            sprintf('%s.id IS NOT NULL', $variantLinkAlias)
        ];
        $config->offsetAddToArrayByPath($orWherePath, $variantOrWherePart);
    }

    /**
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        $datagridConfig = $event->getDatagrid()->getConfig();
        $productRepository = $this->getProductRepository();

        /** @var Product $parentProduct */
        $parentProduct = $productRepository->find($event->getDatagrid()->getParameters()->get('parentProduct'));

        $allCustomFields = $this->customFieldProvider->getEntityCustomFields($this->productClass);
        $variantFields = $parentProduct->getVariantFields();

        foreach ($allCustomFields as $customField) {
            $customFieldName = $customField['name'];
            if (in_array($customFieldName, $variantFields, true)) {
                continue;
            }

            $datagridConfig->removeColumn($customFieldName);
        }
    }

    /**
     * @return EntityRepository
     */
    private function getProductRepository()
    {
        return $this->doctrineHelper->getEntityRepository($this->productClass);
    }

    /**
     * @param DatagridConfiguration $config
     * @return array
     */
    private function getFrom(DatagridConfiguration $config)
    {
        $from = $config->offsetGetByPath('[source][query][from]', []);
        $from = reset($from);

        if (false === $from) {
            throw new \InvalidArgumentException(
                sprintf(
                    '[source][query][from] is missing for grid "%s"',
                    $config->getName()
                )
            );
        }

        return $from;
    }

    /**
     * @param DatagridConfiguration $config
     * @return array
     */
    private function getVariantLinkLeftJoin(DatagridConfiguration $config)
    {
        $leftJoinArray = $config->offsetGetByPath('[source][query][join][left]', []);

        $result = null;
        foreach ($leftJoinArray as $leftJoin) {
            if ($leftJoin['join'] === $this->productVariantLinkClass) {
                $result = $leftJoin;
            }
        }

        if (null === $result) {
            throw new \InvalidArgumentException(
                sprintf(
                    '"%s" is missing in [source][query][join][left] for grid "%s"',
                    $this->productVariantLinkClass,
                    $config->getName()
                )
            );
        }

        return $result;
    }
}
