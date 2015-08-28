<?php

namespace OroB2B\Bundle\ProductBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\PropertyAccess\PropertyAccessor;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Provider\CustomFieldProvider;

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
     * @var PropertyAccessor
     */
    private $accessor;

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
        $this->accessor = new PropertyAccessor();
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();
        $parentProduct = $this->getProductById($event->getDatagrid()->getParameters()->get('parentProduct'));

        foreach ($this->getActualVariantField($parentProduct) as $customField) {
            $columnName = $this->buildColumnName($customField['name']);
            $column = ['label' => $customField['label']];

            $config->offsetSetByPath(sprintf('[columns][%s]', $columnName), $column);
        }
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();

        $parentProduct = $this->getProductById($event->getDatagrid()->getParameters()->get('parentProduct'));
        $customFields = $this->getActualVariantField($parentProduct);

        foreach ($records as $record) {
            $productId = $record->getValue('id');
            $product = $this->getProductById($productId);

            $data = [];
            foreach ($customFields as $customField) {
                $fieldName = $customField['name'];
                $data[$this->buildColumnName($fieldName)] = $this->accessor->getValue($product, $fieldName);
            }
            $record->addData($data);
        }
    }

    /**
     * @param string $fieldName
     * @return string
     */
    protected function buildColumnName($fieldName)
    {
        return $fieldName;
    }

    /**
     * @param int $productId
     * @return null|Product
     */
    private function getProductById($productId)
    {
        return $this->doctrineHelper->getEntityRepository($this->productClass)->find($productId);
    }

    /**
     * @param Product $product
     * @return array
     */
    private function getActualVariantField(Product $product)
    {
        $customFields = [];
        $allCustomFields = $this->customFieldProvider->getEntityCustomFields($this->productClass);

        foreach ($product->getVariantFields() as $fieldName) {
            if (array_key_exists($fieldName, $allCustomFields)) {
                $fieldData = $allCustomFields[$fieldName];
                $customFields[] = [
                    'name' => $fieldData['name'],
                    'label' => $fieldData['label']
                ];
            }
        }

        return $customFields;
    }
}
