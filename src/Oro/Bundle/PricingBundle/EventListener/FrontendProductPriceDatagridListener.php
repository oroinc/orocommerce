<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\PricingBundle\Datagrid\Provider\ProductPriceProvider;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Adds price info to records
 * Modifies data grid settings by adding minimal price column, filter and sorter and prices property
 */
class FrontendProductPriceDatagridListener
{
    const COLUMN_PRICES = 'prices';
    const COLUMN_MINIMAL_PRICE = 'minimal_price';
    const COLUMN_MINIMAL_PRICE_SORT = 'minimal_price_sort';

    /**
     * @var ProductPriceScopeCriteriaRequestHandler
     */
    private $scopeCriteriaRequestHandler;

    /**
     * @var UserCurrencyManager
     */
    private $currencyManager;

    /**
     * @var ProductPriceProvider
     */
    private $combinedProductPriceProvider;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var string
     */
    private $priceColumnNameFilter = WebsiteSearchProductPriceIndexerListener::MP_ALIAS;

    /**
     * @var string
     */
    private $priceColumnNameSorter = 'minimal_price_CPL_ID_CURRENCY';

    /**
     * @param ProductPriceScopeCriteriaRequestHandler $scopeCriteriaRequestHandler
     * @param UserCurrencyManager $currencyManager
     * @param ProductPriceProvider $combinedProductPriceProvider
     * @param TranslatorInterface $translator
     */
    public function __construct(
        ProductPriceScopeCriteriaRequestHandler $scopeCriteriaRequestHandler,
        UserCurrencyManager $currencyManager,
        ProductPriceProvider $combinedProductPriceProvider,
        TranslatorInterface $translator
    ) {
        $this->scopeCriteriaRequestHandler = $scopeCriteriaRequestHandler;
        $this->currencyManager = $currencyManager;
        $this->combinedProductPriceProvider = $combinedProductPriceProvider;
        $this->translator = $translator;
    }

    /**
     * @param string $columnName
     */
    public function setPriceColumnNameForFilter($columnName)
    {
        $this->priceColumnNameFilter = $columnName;
    }

    /**
     * @param string $columnName
     */
    public function setPriceColumnNameForSorter($columnName)
    {
        $this->priceColumnNameSorter = $columnName;
    }

    /**
     * @param SearchResultAfter $event
     */
    public function onResultAfter(SearchResultAfter $event)
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();
        if (\count($records) === 0) {
            return;
        }

        $resultProductPrices = $this->combinedProductPriceProvider->getCombinedPricesForProductsByPriceList(
            $records,
            $this->scopeCriteriaRequestHandler->getPriceScopeCriteria(),
            $this->currencyManager->getUserCurrency()
        );

        foreach ($records as $record) {
            $productId = $record->getValue('id');
            if (array_key_exists($productId, $resultProductPrices)) {
                $record->addData([static::COLUMN_PRICES => $resultProductPrices[$productId]]);
            } else {
                $record->addData([static::COLUMN_PRICES => []]);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();
        $currency = $this->currencyManager->getUserCurrency();
        if (!$currency) {
            return;
        }

        $config->offsetAddToArrayByPath(
            '[properties]',
            [
                self::COLUMN_PRICES => [
                    'type' => 'field',
                    'frontend_type' => PropertyInterface::TYPE_ROW_ARRAY
                ]
            ]
        );

        $config->offsetAddToArrayByPath(
            '[columns]',
            [
                self::COLUMN_MINIMAL_PRICE => [
                    'label' => $this->translator->trans('oro.pricing.productprice.price.label'),
                ],
            ]
        );

        $config->offsetAddToArrayByPath(
            '[filters][columns]',
            [
                self::COLUMN_MINIMAL_PRICE => [
                    'type' => 'frontend-product-price',
                    'data_name' => $this->priceColumnNameFilter
                ],
            ]
        );

        $config->offsetAddToArrayByPath(
            '[columns]',
            [
                self::COLUMN_MINIMAL_PRICE_SORT => [
                    'label' => 'oro.pricing.price.label',
                ],
            ]
        );

        $config->addSorter(
            self::COLUMN_MINIMAL_PRICE_SORT,
            ['data_name' => $this->priceColumnNameSorter, 'type' => 'decimal']
        );
    }
}
