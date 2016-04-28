<?php

namespace OroB2B\Bundle\ShippingBundle\EventListener\Datagrid;

use Doctrine\ORM\Query\Expr;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use Symfony\Component\Translation\TranslatorInterface;

class ProductShippingOptionsDatagridListener
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var string */
    protected $productUnitClass;

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
        $this->addProductShippingOptionsColumn($config);

        $units = $this->getAllUnits();

        // add shipping options for product units
        foreach ($units as $unit) {
            $this->addProductShippingOptionsColumn($config, $unit);
        }
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();
    }

    /**
     * @param DatagridConfiguration $config
     * @param ProductUnit $unit
     */
    protected function addProductShippingOptionsColumn(DatagridConfiguration $config, ProductUnit $unit = null)
    {
        $columnName = $this->buildColumnName($unit);
        $joinAlias = $this->buildJoinAlias($columnName);

        $selectPattern = '%s.id as %s';

        if (!$unit) {
            $selectPattern = 'GROUP_CONCAT(%s.id) as %s';
        }



        $this->addConfigElement($config, '[source][query][select]', sprintf($selectPattern, $joinAlias, $columnName));

        $this->addConfigProductShippingOptionsJoin($config, $unit);

        $renderable = true;

        if ($unit) {
            $renderable = true;
        }

        $column = $this->createShippingOriginColumn($renderable, $unit);

        $this->addConfigElement($config, '[columns]', $column, $columnName);
    }

    /**
     * @param DatagridConfiguration $config
     * @param ProductUnit|null $unit
     */
    protected function addConfigProductShippingOptionsJoin(DatagridConfiguration $config, $unit = null)
    {
        $columnName = $this->buildColumnName($unit);
        $joinAlias = $this->buildJoinAlias($columnName);
        $expr = new Expr();
        $joinExpr = $expr
            ->andX(sprintf('%s.product = product.id', $joinAlias));
        if ($unit) {
            $joinExpr->add($expr->eq(sprintf('%s.productUnit', $joinAlias), $expr->literal($unit)));
        }
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
     * @param bool $renderable
     * @param ProductUnit $unit
     *
     * @return array
     */
    protected function createShippingOriginColumn($renderable = true, ProductUnit $unit = null)
    {
        $message = 'shipping_options';
        $params = [];
        if ($unit) {
            $message = 'shipping_options_%unit%';
            $params['%unit%'] = $unit->getCode();
        }

        return [
            'label' => $this->translator->trans($message, $params),
            'type' => 'twig',
            'template' => 'OroB2BShippingBundle:Datagrid:Column/productShippingOptions.html.twig',
            'frontend_type' => 'html',
            'renderable' => $renderable,
        ];
    }

    /**
     * @param string $productUnitClass
     */
    public function setProductUnitClass($productUnitClass)
    {
        $this->productUnitClass = $productUnitClass;
    }

    /**
     * @param string $productShippingOptionsClass
     */
    public function setProductShippingOptionsClass($productShippingOptionsClass)
    {
        $this->productShippingOptionsClass = $productShippingOptionsClass;
    }

    /**
     * @return array|ProductUnit[]
     */
    protected function getAllUnits()
    {
        return $this->doctrineHelper->getEntityRepository($this->productUnitClass)->findBy([], ['code' => 'ASC']);
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
     * @param string $unitCode
     *
     * @return string
     */
    protected function buildColumnName($unitCode = null)
    {
        $result = 'product_shipping_options';

        return $unitCode ? sprintf('%s_%s', $result, strtolower($unitCode)) : $result;
    }
}
