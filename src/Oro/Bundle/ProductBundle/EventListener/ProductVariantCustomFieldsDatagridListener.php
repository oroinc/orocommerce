<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\ORM\EntityRepository;

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
     * @param DoctrineHelper $doctrineHelper
     * @param CustomFieldProvider $customFieldProvider
     * @param string $productClass
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        CustomFieldProvider $customFieldProvider,
        $productClass
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->customFieldProvider = $customFieldProvider;
        $this->productClass = $productClass;
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

            foreach ($this->getPathsToClear($customFieldName) as $path) {
                $datagridConfig->offsetUnsetByPath($path);
            }
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
     * @param string $field
     * @return array
     */
    private function getPathsToClear($field)
    {
        return [
            sprintf('[%s][%s]', FormatterConfiguration::COLUMNS_KEY, $field),
            sprintf('%s[%s]', SorterConfiguration::COLUMNS_PATH, $field),
            sprintf('%s[%s]', FilterConfiguration::COLUMNS_PATH, $field),
        ];
    }
}
