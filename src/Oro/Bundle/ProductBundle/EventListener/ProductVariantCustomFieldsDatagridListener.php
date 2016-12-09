<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\PropertyAccess\PropertyAccessor;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\CustomVariantFieldsProvider;

class ProductVariantCustomFieldsDatagridListener
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var CustomVariantFieldsProvider
     */
    private $customVariantFieldsProvider;

    /**
     * @var string
     */
    private $productClass;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param CustomVariantFieldsProvider $customVariantFieldsProvider
     * @param string $productClass
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        CustomVariantFieldsProvider $customVariantFieldsProvider,
        $productClass
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->customVariantFieldsProvider = $customVariantFieldsProvider;
        $this->productClass = $productClass;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();

        $productRepository = $this->getProductRepository();
        /** @var Product $parentProduct */
        $parentProduct = $productRepository->find($event->getDatagrid()->getParameters()->get('parentProduct'));

        foreach ($this->getActualVariantFields($parentProduct) as $customField) {
            $columnName = $customField['name'];
            $column = ['label' => $customField['label']];

            $config->offsetSetByPath(sprintf('[columns][%s]', $columnName), $column);
        }
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        $propertyAccessor = new PropertyAccessor();

        /** @var ResultRecord[] $records */
        $records = $event->getRecords();

        $productRepository = $this->getProductRepository();
        /** @var Product $parentProduct */
        $parentProduct = $productRepository->find($event->getDatagrid()->getParameters()->get('parentProduct'));
        $customFields = $this->getActualVariantFields($parentProduct);

        foreach ($records as $record) {
            $productId = $record->getValue('id');
            $product = $productRepository->find($productId);

            $data = [];
            foreach ($customFields as $customField) {
                $fieldName = $customField['name'];
                $data[$fieldName] = $propertyAccessor->getValue($product, $fieldName);
            }
            $record->addData($data);
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
     * @param Product $product
     * @return array
     */
    private function getActualVariantFields(Product $product)
    {
        $customFields = [];
        $entityCustomVariantFields = $this->customVariantFieldsProvider->getEntityCustomFields($this->productClass);

        foreach ($product->getVariantFields() as $fieldName) {
            if (array_key_exists($fieldName, $entityCustomVariantFields)) {
                $fieldData = $entityCustomVariantFields[$fieldName];
                $customFields[] = [
                    'name' => $fieldData['name'],
                    'label' => $fieldData['label']
                ];
            }
        }

        return $customFields;
    }
}
