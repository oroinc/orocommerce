<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Symfony\Component\Translation\TranslatorInterface;

use Doctrine\ORM\Query\Expr;
use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

use OroB2B\Bundle\PricingBundle\Entity\CombinedProductPrice;
use OroB2B\Bundle\PricingBundle\Provider\UserCurrencyProvider;
use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;

class FrontendProductPriceDatagridListener extends AbstractProductPriceDatagridListener
{
    const COLUMN_PRICES = 'prices';
    const COLUMN_MINIMUM_PRICE = 'minimum_price';
    const JOIN_ALIAS_PRICE = 'product_price';

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
     * @var Registry
     */
    protected $registry;

    /**
     * @param TranslatorInterface $translator
     * @param PriceListRequestHandler $priceListRequestHandler
     * @param NumberFormatter $numberFormatter
     * @param ProductUnitLabelFormatter $unitLabelFormatter
     * @param ProductUnitValueFormatter $unitValueFormatter
     * @param UserCurrencyProvider $currencyProvider
     * @param Registry $registry
     */
    public function __construct(
        TranslatorInterface $translator,
        PriceListRequestHandler $priceListRequestHandler,
        NumberFormatter $numberFormatter,
        ProductUnitLabelFormatter $unitLabelFormatter,
        ProductUnitValueFormatter $unitValueFormatter,
        UserCurrencyProvider $currencyProvider,
        Registry $registry
    ) {
        parent::__construct($translator, $priceListRequestHandler);
        $this->numberFormatter = $numberFormatter;
        $this->unitLabelFormatter = $unitLabelFormatter;
        $this->unitValueFormatter = $unitValueFormatter;
        $this->currencyProvider = $currencyProvider;
        $this->registry = $registry;
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();

        $productIds = [];
        foreach ($records as $record) {
            $productIds[] = $record->getValue('id');
        }

        $priceList = $this->priceListRequestHandler->getPriceListByAccount();

        $currency = $this->getCurrencies()[0];
        $combinedPrices = $this->registry->getManagerForClass('OroB2BPricingBundle:CombinedProductPrice')
            ->getRepository('OroB2BPricingBundle:CombinedProductPrice')
            ->getPricesForProductsByPriceList($priceList, $productIds, $currency);

        $resultProductPrices = [];
        foreach ($combinedPrices as $price) {
            $index = sprintf('%s_%s', $price->getUnit()->getCode(), $price->getQuantity());

            $productId = $price->getProduct()->getId();
            if (isset($resultProductPrices[$productId][$index])) {
                continue;
            }

            $resultProductPrices[$productId][$index] = $this->prepareResultPrice($price);
        }

        foreach ($records as $record) {
            $record->addData([
                self::COLUMN_PRICES => $resultProductPrices[$record->getValue('id')]
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

        $minimumPriceColumnName = self::COLUMN_MINIMUM_PRICE;

        $this->addConfigProductPriceJoin($config, $currency);

        $selectPattern = '%s.value as %s';
        $select = sprintf($selectPattern, self::JOIN_ALIAS_PRICE, $minimumPriceColumnName);
        $this->addConfigElement($config, '[source][query][select]', $select);

        $this->addConfigElement(
            $config,
            '[properties]',
            [
                'type' => 'field',
                'frontend_type' => PropertyInterface::TYPE_ROW_ARRAY
            ],
            self::COLUMN_PRICES
        );

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

    /**
     * @param CombinedProductPrice $price
     * @return array
     */
    protected function prepareResultPrice(CombinedProductPrice $price)
    {
        $priceValue = $price->getPrice()->getValue();
        $unitCode = $price->getUnit()->getCode();
        $quantity = $price->getQuantity();
        $currencyIsoCode = $price->getPrice()->getCurrency();

        $resultPrices = [
            'price' => $priceValue,
            'currency' => $currencyIsoCode,
            'formatted_price' => $this->numberFormatter->formatCurrency($priceValue, $currencyIsoCode),
            'unit' => $unitCode,
            'formatted_unit' => $this->unitLabelFormatter->format($unitCode),
            'quantity' => $quantity,
            'quantity_with_unit' => $this->unitValueFormatter->formatCode($quantity, $unitCode)
        ];

        return $resultPrices;
    }
}
