<?php

namespace OroB2B\Bundle\ProductBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Component\PropertyAccess\PropertyAccessor;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class ProductVariantCustomFieldsDatagridListener
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var ConfigManager
     */
    private $configManager;

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
     * @param ConfigManager $configManager
     * @param string $productClass
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ConfigManager $configManager,
        $productClass
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
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
        return 'custom_field_' . strtolower($fieldName);
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
        $configProvider = $this->getEntityConfigProvider();

        foreach ($product->getVariantFields() as $fieldName) {
            if ($configProvider->hasConfig($this->productClass, $fieldName)) {
                $fieldConfig = $configProvider->getConfig($this->productClass, $fieldName);
                $customFields[] = [
                    'name' => $fieldName,
                    'label' => $fieldConfig->get('label')
                ];
            }
        }

        return $customFields;
    }

    /**
     * @return ConfigProvider
     */
    private function getEntityConfigProvider()
    {
        return $this->configManager->getProvider('entity');
    }
}
