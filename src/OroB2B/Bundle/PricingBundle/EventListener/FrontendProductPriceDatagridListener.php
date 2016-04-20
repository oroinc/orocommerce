<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Symfony\Component\Translation\TranslatorInterface;

use Doctrine\ORM\Query\Expr;

use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

use OroB2B\Bundle\PricingBundle\Provider\UserCurrencyProvider;
use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;

class FrontendProductPriceDatagridListener extends AbstractProductPriceDatagridListener
{
    const COLUMN_PRICES = 'prices';
    const COLUMN_UNITS = 'price_units';
    const COLUMN_QUANTITIES = 'price_quantities';
    const JOIN_ALIAS_PRICE = 'product_price';
    const COLUMN_MINIMUM_PRICE = 'minimum_price';

    const DATA_SEPARATOR = '{sep}';

    /**
     * @var NumberFormatter
     */
    protected $numberFormatter;

    /**
     * @var ProductUnitLabelFormatter
     */
    protected $unitLabelFormatter;

    /**
     * @var ProductUnitValueFormatter
     */
    protected $unitValueFormatter;

    /**
     * @var UserCurrencyProvider
     */
    protected $currencyProvider;

    /**
     * @param TranslatorInterface $translator
     * @param PriceListRequestHandler $priceListRequestHandler
     * @param NumberFormatter $numberFormatter
     * @param ProductUnitLabelFormatter $unitLabelFormatter
     * @param ProductUnitValueFormatter $unitValueFormatter
     * @param UserCurrencyProvider $currencyProvider
     */
    public function __construct(
        TranslatorInterface $translator,
        PriceListRequestHandler $priceListRequestHandler,
        NumberFormatter $numberFormatter,
        ProductUnitLabelFormatter $unitLabelFormatter,
        ProductUnitValueFormatter $unitValueFormatter,
        UserCurrencyProvider $currencyProvider
    ) {
        parent::__construct($translator, $priceListRequestHandler);
        $this->numberFormatter = $numberFormatter;
        $this->unitLabelFormatter = $unitLabelFormatter;
        $this->unitValueFormatter = $unitValueFormatter;
        $this->currencyProvider = $currencyProvider;
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();

        $currencyIsoCode = $this->getCurrencies()[0];
        $priceColumn = self::COLUMN_PRICES;
        foreach ($records as $record) {
            $resultPrices = [];
            $prices = $this->parseArrayValue($record, $priceColumn);
            $units = $this->parseArrayValue($record, self::COLUMN_UNITS);
            $quantities = $this->parseArrayValue($record, self::COLUMN_QUANTITIES);
            if ($prices) {
                foreach ($prices as $key => $price) {
                    // order of all parts is the same
                    $price = (double)$price;
                    $unit = $units[$key];
                    $quantity = (double)$quantities[$key];
                    $index = sprintf('%s_%s', $unit, $quantity);

                    if (isset($resultPrices[$index])) { // there might be duplicated because of multiple units
                        continue;
                    }

                    $resultPrices[$index] = [
                        'price' => $price,
                        'currency' => $currencyIsoCode,
                        'formatted_price' => $this->numberFormatter->formatCurrency($price, $currencyIsoCode),
                        'unit' => $unit,
                        'formatted_unit' => $this->unitLabelFormatter->format($unit),
                        'quantity' => $quantity,
                        'quantity_with_unit' => $this->unitValueFormatter->formatCode($quantity, $unit)
                    ];
                }
            }
            $record->addData([
                $priceColumn => $resultPrices,
                self::COLUMN_UNITS => null,
                self::COLUMN_QUANTITIES => null
            ]);
        }
    }



    /**
     * {@inheritDoc}
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();
        $currency = $this->getCurrencies()[0];
        if (!$currency) {
            return;
        }

        $pricesColumnName = self::COLUMN_PRICES;
        $unitColumnName = self::COLUMN_UNITS;
        $quantitiesColumnName = self::COLUMN_QUANTITIES;
        $minimumPriceColumnName = self::COLUMN_MINIMUM_PRICE;
        $joinAlias = self::JOIN_ALIAS_PRICE;
        $separator = (new Expr())->literal(self::DATA_SEPARATOR);

        $selectPattern = 'GROUP_CONCAT(%s.value SEPARATOR %s) as %s';
        $select = sprintf($selectPattern, $joinAlias, $separator, $pricesColumnName);
        $this->addConfigElement($config, '[source][query][select]', $select);

        $selectPattern = 'GROUP_CONCAT(IDENTITY(%s.unit) SEPARATOR %s) as %s';
        $select = sprintf($selectPattern, $joinAlias, $separator, $unitColumnName);
        $this->addConfigElement($config, '[source][query][select]', $select);

        $selectPattern = 'GROUP_CONCAT(%s.quantity SEPARATOR %s) as %s';
        $select = sprintf($selectPattern, $joinAlias, $separator, $quantitiesColumnName);
        $this->addConfigElement($config, '[source][query][select]', $select);

        $selectPattern = 'MIN(%s.value) as %s';
        $select = sprintf($selectPattern, $joinAlias, $minimumPriceColumnName);
        $this->addConfigElement($config, '[source][query][select]', $select);

        $this->addConfigProductPriceJoin($config, $currency);

        $this->addConfigElement(
            $config,
            '[properties]',
            ['type' => 'field', 'frontend_type' => PropertyInterface::TYPE_ROW_ARRAY],
            $pricesColumnName
        );
        $this->addConfigElement($config, '[properties]', null, $unitColumnName);
        $this->addConfigElement($config, '[properties]', null, $quantitiesColumnName);

        $this->addConfigElement(
            $config,
            '[columns]',
            [
                'label' => $this->translator->trans('orob2b.pricing.productprice.price_in_%currency%', [
                    '%currency%' => $currency
                ])
            ],
            $minimumPriceColumnName
        );
        $this->addConfigElement(
            $config,
            '[sorters][columns]',
            [
                'data_name' => $minimumPriceColumnName,
                'type' => PropertyInterface::TYPE_CURRENCY,
            ],
            $minimumPriceColumnName
        );

        $filter = ['type' => 'frontend-product-price', 'data_name' => $currency];
        $this->addConfigElement($config, '[filters][columns]', $filter, $minimumPriceColumnName);
    }

    /**
     * {@inheritDoc}
     */
    protected function providePriceList()
    {
        return $this->priceListRequestHandler->getPriceListByAccount();
    }

    /**
     * @return array
     */
    protected function getCurrencies()
    {
        return [$this->currencyProvider->getUserCurrency()];
    }

    /**
     * @param DatagridConfiguration $config
     * @param string $currency
     */
    protected function addConfigProductPriceJoin(DatagridConfiguration $config, $currency)
    {
        $joinAlias = self::JOIN_ALIAS_PRICE;
        $priceList = $this->getPriceList();
        $expr = new Expr();
        $joinExpr = $expr
            ->andX(sprintf('%s.product = product.id', $joinAlias))
            ->add($expr->eq(sprintf('%s.currency', $joinAlias), $expr->literal($currency)))
            ->add($expr->eq(sprintf('%s.priceList', $joinAlias), $expr->literal($priceList->getId())));
        $this->addConfigElement($config, '[source][query][join][left]', [
            'join' => $this->productPriceClass,
            'alias' => $joinAlias,
            'conditionType' => Expr\Join::WITH,
            'condition' => (string)$joinExpr,
        ]);
    }

    /**
     * @param ResultRecord $record
     * @param string $columnName
     * @return array
     */
    protected function parseArrayValue(ResultRecord $record, $columnName)
    {
        $values = $record->getValue($columnName);
        return $values ? explode(self::DATA_SEPARATOR, $values) : [];
    }
}
