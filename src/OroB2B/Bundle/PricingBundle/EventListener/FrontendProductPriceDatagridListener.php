<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;

class FrontendProductPriceDatagridListener extends AbstractProductPriceDatagridListener
{
    /**
     * @var NumberFormatter
     */
    protected $numberFormatter;

    /**
     * @param TranslatorInterface $translator
     * @param PriceListRequestHandler $priceListRequestHandler
     * @param NumberFormatter $numberFormatter
     */
    public function __construct(
        TranslatorInterface $translator,
        PriceListRequestHandler $priceListRequestHandler,
        NumberFormatter $numberFormatter
    ) {
        parent::__construct($translator, $priceListRequestHandler);
        $this->numberFormatter = $numberFormatter;
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        $currencies = $this->getCurrencies();
        if (!$currencies) {
            return;
        }

        /** @var ResultRecord[] $records */
        $records = $event->getRecords();

        $currencyIsoCode = $this->getCurrency();
        $priceColumn = $this->buildColumnName($currencyIsoCode);
        foreach ($records as $record) {
            $price = $record->getValue($priceColumn);
            if ($price) {
                $price = $this->numberFormatter->formatCurrency($record->getValue($priceColumn), $currencyIsoCode);
                $record->addData([$priceColumn => $price]);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $currencies = $this->getCurrencies();
        if (!$currencies) {
            return;
        }
        $config = $event->getConfig();
        $currency = $this->getCurrency();

        $columnName = $this->buildColumnName($currency);
        $unitColumnName = $this->buildUnitColumnName();

        $joinAlias = $this->buildJoinAlias($columnName);
        $selectPattern = 'min(%s.value) as %s';
        $select = sprintf($selectPattern, $joinAlias, $columnName);
        $this->addConfigElement($config, '[source][query][select]', $select);
        $selectPattern = '(%s.unit) as %s';
        $select = sprintf($selectPattern, $joinAlias, $unitColumnName);
        $this->addConfigElement($config, '[source][query][select]', $select);
        $this->addConfigProductPriceJoin($config, $currency);

        $this->addConfigElement($config, '[columns]', $this->createPriceColumn($currency), $columnName);
        $this->addConfigElement($config, '[columns]', [
            'label' => $this->translator->trans('orob2b.product.productunit.entity_label')
        ], $unitColumnName);

        $this->addConfigElement($config, '[sorters][columns]', $this->getSorter($columnName), $columnName);

        $this->addConfigFilter($config, $currency);
    }

    /**
     * @param $columnName
     * @return array
     */
    protected function getSorter($columnName)
    {
        return [
            'data_name' => $columnName,
            'type' => PropertyInterface::TYPE_CURRENCY,
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function buildColumnName($currencyIsoCode, $unitCode = null)
    {
        return 'price_column';
    }

    /**
     * @return string
     */
    protected function buildUnitColumnName()
    {
        return 'price_unit_column';
    }

    /**
     * @param string $currency
     * @return array
     */
    protected function createPriceColumn($currency)
    {
        return [
            'label' => $this->translator->trans('orob2b.pricing.productprice.price_in_%currency%', [
                '%currency%' => $currency
            ]),
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function providePriceList()
    {
        return $this->priceListRequestHandler->getPriceListByAccount();
    }

    /**
     * @return string
     */
    protected function getCurrency()
    {
        $currencies = $this->getCurrencies();
        return reset($currencies);
    }
}
