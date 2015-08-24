<?php

namespace OroB2B\Bundle\ProductBundle\EventListener;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class ProductVariantCustomFieldsDatagridListener
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

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
     * @param TranslatorInterface $translator
     * @param DoctrineHelper $doctrineHelper
     * @param ConfigManager $configManager
     * @param string $productClass
     */
    public function __construct(
        TranslatorInterface $translator,
        DoctrineHelper $doctrineHelper,
        ConfigManager $configManager,
        $productClass
    ) {
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
        $this->productClass = $productClass;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();

        foreach ($this->getProductCustomFields() as $customField) {
            $columnName = $this->buildColumnName($customField);
            $column = ['label' => ucfirst($customField),];

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

        foreach ($records as $record) {
            $productId = $record->getValue('id');
            $product = $this->doctrineHelper->getEntityRepository($this->productClass)->find($productId);

            $data = [];
            foreach ($this->getProductCustomFields() as $customField) {
                $data[$this->buildColumnName($customField)] = $product->{'get' . ucfirst($customField)}();
            }
            $record->addData($data);
        }
    }

    /**
     * @param string $customField
     * @return string
     */
    protected function buildColumnName($customField)
    {
        return 'custom_field_' . strtolower($customField);
    }

    /**
     * @return array
     */
    private function getProductCustomFields()
    {
        $extendConfig = $this->configManager->getProvider('extend')->getConfig($this->productClass);
        $schema = $extendConfig->get('schema');
        $customProperties = $schema['property'];
        unset($customProperties['serialized_data']);

        return $customProperties;
    }
}
