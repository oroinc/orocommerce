<?php

namespace OroB2B\Bundle\ShippingBundle\EventListener\Datagrid;

use Doctrine\ORM\Query\Expr;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptions;

class ProductShippingOptionsDatagridListener
{
    const SHIPPING_OPTIONS_COLUMN = 'product_shipping_options';
    const DATA_SEPARATOR = '{sep}';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var string */
    protected $productShippingOptionsClass;

    /**
     * @param TranslatorInterface $translator
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(TranslatorInterface $translator, DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->translator = $translator;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();

        $this->addConfigSelect($config, static::SHIPPING_OPTIONS_COLUMN);
        $this->addConfigJoin($config, static::SHIPPING_OPTIONS_COLUMN);

        $column = [
            'label' => $this->translator->trans('orob2b.shipping.datagrid.shipping_options.column.label'),
            'type' => 'twig',
            'template' => 'OroB2BShippingBundle:Datagrid:Column/productShippingOptions.html.twig',
            'frontend_type' => 'html',
            'renderable' => false,
        ];

        $this->addConfigElement($config, '[columns]', $column, static::SHIPPING_OPTIONS_COLUMN);
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();

        foreach ($records as $record) {
            $data = [];
            $shippingOptions = $record->getValue(static::SHIPPING_OPTIONS_COLUMN);
            $shippingOptions = $shippingOptions ? explode(self::DATA_SEPARATOR, $shippingOptions) : [];
            foreach ($shippingOptions as $optionId) {
                /* @var $options ProductShippingOptions */
                $options = $this->doctrineHelper->getEntityReference($this->productShippingOptionsClass, $optionId);
                if ($options) {
                    $data[$optionId] = $options;
                }
            }
            $record->addData([static::SHIPPING_OPTIONS_COLUMN => $data]);
        }
    }

    /**
     * @param DatagridConfiguration $config
     * @param string $columnName
     */
    protected function addConfigSelect(DatagridConfiguration $config, $columnName)
    {
        $joinAlias = $this->buildJoinAlias($columnName);

        $selectPattern = 'GROUP_CONCAT(%s.id SEPARATOR %s) as %s';
        $separator = (new Expr())->literal(self::DATA_SEPARATOR);

        $this->addConfigElement(
            $config,
            '[source][query][select]',
            sprintf($selectPattern, $joinAlias, $separator, $columnName)
        );
    }

    /**
     * @param DatagridConfiguration $config
     * @param string $columnName
     */
    protected function addConfigJoin(DatagridConfiguration $config, $columnName)
    {
        $joinAlias = $this->buildJoinAlias($columnName);

        $joinExpr = (new Expr())->andX(sprintf('%s.product = product.id', $joinAlias));

        $this->addConfigElement(
            $config,
            '[source][query][join][left]',
            [
                'join' => $this->productShippingOptionsClass,
                'alias' => $joinAlias,
                'conditionType' => Expr\Join::WITH,
                'condition' => (string)$joinExpr,
            ]
        );
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
     * @param string $productShippingOptionsClass
     */
    public function setProductShippingOptionsClass($productShippingOptionsClass)
    {
        $this->productShippingOptionsClass = $productShippingOptionsClass;
    }

    /**
     * @param string $columnName
     *
     * @return string
     */
    protected function buildJoinAlias($columnName)
    {
        return $columnName . '_table';
    }
}
