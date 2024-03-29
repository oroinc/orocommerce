<?php

namespace Oro\Bundle\ShippingBundle\EventListener\Datagrid;

use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;

/**
 * Adds column with Product Shipping Options information to the Product grid.
 */
class ProductShippingOptionsDatagridListener
{
    const SHIPPING_OPTIONS_COLUMN = 'product_shipping_options';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var string */
    protected $productShippingOptionsClass;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param string $productShippingOptionsClass
     */
    public function setProductShippingOptionsClass($productShippingOptionsClass)
    {
        $this->productShippingOptionsClass = $productShippingOptionsClass;
    }

    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();
        $column = [
            'label' => 'oro.shipping.datagrid.shipping_options.column.label',
            'type' => 'twig',
            'template' => '@OroShipping/Datagrid/Column/productShippingOptions.html.twig',
            'frontend_type' => 'html',
            'renderable' => false,
        ];

        $field_acl = [
            'data_name' => 'product.unitPrecisions',
            'source_name' => 'unitPrecisions',
            'column_name' => static::SHIPPING_OPTIONS_COLUMN
        ];

        $this->addConfigElement($config, '[columns]', $column, static::SHIPPING_OPTIONS_COLUMN);
        $this->addConfigElement($config, '[fields_acl][columns]', $field_acl, static::SHIPPING_OPTIONS_COLUMN);
    }

    public function onResultAfter(OrmResultAfter $event)
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();

        $productIds = array_map(
            function (ResultRecord $record) {
                return $record->getValue('id');
            },
            $records
        );

        $this->addProductShippingOptions($productIds, $records);
    }

    /**
     * @param array $productIds
     * @param array|ResultRecord[] $records
     */
    protected function addProductShippingOptions(array $productIds, array $records)
    {
        $groupedOptions = $this->getShippingOptions($productIds);

        foreach ($records as $record) {
            $data = [];
            $productId = $record->getValue('id');

            if (array_key_exists($productId, $groupedOptions)) {
                $data = $groupedOptions[$productId];
            }

            $record->addData([self::SHIPPING_OPTIONS_COLUMN => $data]);
        }
    }

    /**
     * @param DatagridConfiguration $config
     * @param string $path
     * @param mixed $element
     * @param mixed $key
     */
    protected function addConfigElement(DatagridConfiguration $config, $path, $element, $key = null)
    {
        $select = $config->offsetGetByPath($path);
        if ($key) {
            $select[$key] = $element;
        } else {
            $select[] = $element;
        }
        $config->offsetSetByPath($path, $select);
    }

    /**
     * @return ObjectRepository
     */
    protected function getRepository()
    {
        return $this->doctrineHelper->getEntityRepository($this->productShippingOptionsClass);
    }

    /**
     * @param array $productIds
     * @return array
     */
    protected function getShippingOptions(array $productIds)
    {
        /** @var ProductShippingOptions[] $options */
        $options = $this->getRepository()->findBy(['product' => $productIds], ['productUnit' => 'ASC']);

        $result = [];
        foreach ($options as $option) {
            $result[$option->getProduct()->getId()][] = $option;
        }

        return $result;
    }
}
