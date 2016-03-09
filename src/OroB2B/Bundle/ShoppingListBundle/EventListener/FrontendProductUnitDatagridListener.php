<?php

namespace OroB2B\Bundle\ShoppingListBundle\EventListener;

use Doctrine\ORM\Query\Expr;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;

class FrontendProductUnitDatagridListener
{
    const PRODUCT_UNITS_COLUMN_NAME = 'units';
    const PRODUCT_UNITS_SEPARATOR = '{sep}';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var ProductUnitLabelFormatter
     */
    protected $productUnitLabelFormatter;

    /**
     * @param TranslatorInterface $translator
     * @param DoctrineHelper $doctrineHelper
     * @param ProductUnitLabelFormatter $productUnitLabelFormatter
     */
    public function __construct(
        TranslatorInterface $translator,
        DoctrineHelper $doctrineHelper,
        ProductUnitLabelFormatter $productUnitLabelFormatter
    ) {
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
        $this->productUnitLabelFormatter = $productUnitLabelFormatter;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();
        $this->addConfigElement($config, '[columns]', [
            'label' => $this->translator->trans('orob2b.shoppinglist.lineitem.unit.label'),
            'frontend_type' => PropertyInterface::TYPE_ARRAY,
        ], self::PRODUCT_UNITS_COLUMN_NAME);

        $unitPrecisionsAlias = $this->getJoinAlias('unitPrecisions');
        $unitAlias = $this->getJoinAlias('unit');
        $select = sprintf(
            'GROUP_CONCAT(%s.code SEPARATOR %s) as %s',
            $unitAlias,
            (new Expr())->literal(self::PRODUCT_UNITS_SEPARATOR),
            $this->getSelectAlias()
        );
        $this->addConfigElement($config, '[source][query][select]', $select);
        $this->addConfigElement($config, '[source][query][join][left]', [
            'join' => 'product.unitPrecisions',
            'alias' => $unitPrecisionsAlias,
        ]);
        $this->addConfigElement($config, '[source][query][join][left]', [
            'join' => sprintf('%s.unit', $unitPrecisionsAlias),
            'alias' => $unitAlias,
        ]);
    }

    /**
     * @return string
     */
    protected function getSelectAlias()
    {
        return 'shopping_list_form_units';
    }

    /**
     * @param string $column
     * @return string
     */
    protected function getJoinAlias($column)
    {
        return 'shopping_list_form_' . $column;
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();
        foreach ($records as $record) {
            $choices = [];
            $unitsString = $record->getValue($this->getSelectAlias());
            if ($unitsString) {
                $productUnits = explode(self::PRODUCT_UNITS_SEPARATOR, $unitsString);
                $choices = $this->productUnitLabelFormatter->formatChoicesByCodes($productUnits);
            }
            $record->addData([self::PRODUCT_UNITS_COLUMN_NAME => $choices]);
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
}
