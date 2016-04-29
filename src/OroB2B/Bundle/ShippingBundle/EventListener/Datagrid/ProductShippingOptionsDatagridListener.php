<?php

namespace OroB2B\Bundle\ShippingBundle\EventListener\Datagrid;

use Doctrine\ORM\Query\Expr;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

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
        $columnName = $this->buildColumnName();
        $joinAlias = $this->buildJoinAlias($columnName);

        $selectPattern = 'GROUP_CONCAT(%s.id SEPARATOR %s) as %s';
        $separator = (new Expr())->literal(self::DATA_SEPARATOR);

        $this->addConfigElement(
            $config,
            '[source][query][select]',
            sprintf($selectPattern, $joinAlias, $separator, $columnName)
        );

        $this->addConfigProductShippingOptionsJoin($config);

        $columnLabel = 'orob2b.shipping.datagrid.shipping_options.column.label';
        $params = [];

        $column = [
            'label' => $this->translator->trans($columnLabel, $params),
            'type' => 'twig',
            'template' => $this->getColumnTemplate(),
            'frontend_type' => 'html',
            'renderable' => true,
        ];

        $this->addConfigElement($config, '[columns]', $column, $columnName);
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
     */
    protected function addConfigProductShippingOptionsJoin(DatagridConfiguration $config)
    {
        $columnName = $this->buildColumnName();
        $joinAlias = $this->buildJoinAlias($columnName);
        $expr = new Expr();
        $joinExpr = $expr
            ->andX(sprintf('%s.product = product.id', $joinAlias));

        $this->addConfigElement(
            $config,
            '[source][query][join][left]',
            [
                'join' => $this->productShippingOptionsClass,
                'alias' => $joinAlias,
                'conditionType' => Expr\Join::WITH,
                'condition' => (string) $joinExpr,
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

    /**
     * @return string
     */
    protected function buildColumnName()
    {
        return static::SHIPPING_OPTIONS_COLUMN;
    }

    /**
     * @return string
     */
    protected function getColumnTemplate()
    {
        return 'OroB2BShippingBundle:Datagrid:Column/productShippingOptions.html.twig';
    }
}
