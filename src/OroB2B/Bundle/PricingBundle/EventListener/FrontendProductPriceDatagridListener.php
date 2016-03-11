<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Symfony\Component\Translation\TranslatorInterface;

use Doctrine\ORM\Query\Expr;

use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

use OroB2B\Bundle\PricingBundle\Provider\UserCurrencyProvider;
use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;

class FrontendProductPriceDatagridListener extends AbstractProductPriceDatagridListener
{
    const COLUMN_UNITS = 'price_units';
    const COLUMN_MINIMUM_PRICE = 'minimum_price';

    const DATA_SEPARATOR = '{sep}';

    /**
     * @var NumberFormatter
     */
    protected $numberFormatter;

    /**
     * @var ProductUnitLabelFormatter
     */
    protected $productUnitLabelFormatter;

    /**
     * @var UserCurrencyProvider
     */
    protected $currencyProvider;

    /**
     * @param TranslatorInterface $translator
     * @param PriceListRequestHandler $priceListRequestHandler
     * @param NumberFormatter $numberFormatter
     * @param ProductUnitLabelFormatter $productUnitLabelFormatter
     * @param UserCurrencyProvider $currencyProvider
     */
    public function __construct(
        TranslatorInterface $translator,
        PriceListRequestHandler $priceListRequestHandler,
        NumberFormatter $numberFormatter,
        ProductUnitLabelFormatter $productUnitLabelFormatter,
        UserCurrencyProvider $currencyProvider
    ) {
        parent::__construct($translator, $priceListRequestHandler);
        $this->numberFormatter = $numberFormatter;
        $this->productUnitLabelFormatter = $productUnitLabelFormatter;
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
        $priceColumn = $this->buildColumnName($currencyIsoCode);
        foreach ($records as $record) {
            $resultPrices = [];
            $prices = $record->getValue($priceColumn);
            $prices = $prices ? explode(self::DATA_SEPARATOR, $prices) : [];
            $units = $record->getValue(self::COLUMN_UNITS);
            $units = $units ? explode(self::DATA_SEPARATOR, $units) : [];
            if ($prices) {
                foreach ($units as $key => $unit) {
                    if (isset($resultPrices[$unit])) { // there might be duplicated because of multiple units
                        continue;
                    }
                    $price = (double)$prices[$key]; // order of prices and units is the same
                    $resultPrices[$unit] = [
                        'price' => $price,
                        'currency' => $currencyIsoCode,
                        'formatted_price' => $this->numberFormatter->formatCurrency($price, $currencyIsoCode),
                        'unit' => $unit,
                        'formatted_unit' => $this->productUnitLabelFormatter->format($unit)
                    ];
                }
            }
            $record->addData([$priceColumn => $resultPrices, self::COLUMN_UNITS => null]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();
        $currency = $this->getCurrencies()[0];

        $pricesColumnName = $this->buildColumnName($currency);
        $separator = (new Expr())->literal(self::DATA_SEPARATOR);
        $joinAlias = $this->buildJoinAlias($pricesColumnName);

        $selectPattern = 'GROUP_CONCAT(%s.value SEPARATOR %s) as %s';
        $select = sprintf($selectPattern, $joinAlias, $separator, $pricesColumnName);
        $this->addConfigElement($config, '[source][query][select]', $select);

        $selectPattern = 'GROUP_CONCAT(%s.unit SEPARATOR %s) as %s';
        $select = sprintf($selectPattern, $joinAlias, $separator, self::COLUMN_UNITS);
        $this->addConfigElement($config, '[source][query][select]', $select);

        $selectPattern = 'min(%s.value) as %s';
        $select = sprintf($selectPattern, $joinAlias, self::COLUMN_MINIMUM_PRICE);
        $this->addConfigElement($config, '[source][query][select]', $select);

        $this->addConfigProductPriceJoin($config, $currency);

        $this->addConfigElement(
            $config,
            '[properties]',
            ['type' => 'field', 'frontend_type' => PropertyInterface::TYPE_ROW_ARRAY],
            $pricesColumnName
        );
        $this->addConfigElement($config, '[properties]', null, self::COLUMN_UNITS);

        $this->addConfigElement(
            $config,
            '[columns]',
            [
                'label' => $this->translator->trans('orob2b.pricing.productprice.price_in_%currency%', [
                    '%currency%' => $currency
                ]),
            ],
            self::COLUMN_MINIMUM_PRICE
        );
        $this->addConfigElement(
            $config,
            '[sorters][columns]',
            [
                'data_name' => self::COLUMN_MINIMUM_PRICE,
                'type' => PropertyInterface::TYPE_CURRENCY,
            ],
            self::COLUMN_MINIMUM_PRICE
        );

        $this->addConfigFilter($config, $currency);
    }

    /**
     * {@inheritDoc}
     */
    protected function buildColumnName($currencyIsoCode, $unitCode = null)
    {
        return 'prices';
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
}
