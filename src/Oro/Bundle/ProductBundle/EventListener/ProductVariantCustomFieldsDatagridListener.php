<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\DataGridBundle\Event\BuildAfter;
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
