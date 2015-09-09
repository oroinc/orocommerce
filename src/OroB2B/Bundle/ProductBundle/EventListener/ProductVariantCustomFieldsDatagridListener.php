<?php

namespace OroB2B\Bundle\ProductBundle\EventListener;

use Doctrine\ORM\EntityRepository;

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

        $productRepository = $this->getProductRepository();
        /** @var Product $parentProduct */
        $parentProduct = $productRepository->find($event->getDatagrid()->getParameters()->get('parentProduct'));

        foreach ($this->getActualVariantField($parentProduct) as $customField) {
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
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();

        $productRepository = $this->getProductRepository();
        /** @var Product $parentProduct */
        $parentProduct = $productRepository->find($event->getDatagrid()->getParameters()->get('parentProduct'));
        $customFields = $this->getActualVariantField($parentProduct);

        foreach ($records as $record) {
            $productId = $record->getValue('id');
            $product = $productRepository->find($productId);

            $data = [];
            foreach ($customFields as $customField) {
                $fieldName = $customField['name'];
                $data[$fieldName] = $this->accessor->getValue($product, $fieldName);
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
