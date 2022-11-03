<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\PricingBundle\Datagrid\Provider\ProductPriceProvider;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Adds price info to records
 * Modifies data grid settings by adding minimal price column, filter and sorter and prices property
 */
class FrontendProductPriceDatagridListener implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

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
    private $productPriceProvider;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var string
     */
    private $priceColumnNameFilter;

    /**
     * @var string
     */
    private $priceColumnNameSorter;

    public function __construct(
        ProductPriceScopeCriteriaRequestHandler $scopeCriteriaRequestHandler,
        UserCurrencyManager $currencyManager,
        ProductPriceProvider $productPriceProvider,
        TranslatorInterface $translator
    ) {
        $this->scopeCriteriaRequestHandler = $scopeCriteriaRequestHandler;
        $this->currencyManager = $currencyManager;
        $this->productPriceProvider = $productPriceProvider;
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

    public function onResultAfter(SearchResultAfter $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        /** @var ResultRecord[] $records */
        $records = $event->getRecords();
        if (\count($records) === 0) {
            return;
        }

        $resultProductPrices = $this->productPriceProvider->getPricesForProductsByPriceList(
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
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $sortColumn = $this->priceColumnNameSorter;
        $filterColumn = $this->priceColumnNameFilter;
        $isFlatPricing = $this->featureChecker->isFeatureEnabled('oro_price_lists_flat');
        if (!$sortColumn) {
            $sortColumn = $isFlatPricing ? 'minimal_price.PRICE_LIST_ID_CURRENCY' : 'minimal_price.CPL_ID_CURRENCY';
        }
        if (!$filterColumn) {
            $filterColumn = $isFlatPricing
                ? WebsiteSearchProductPriceFlatIndexerListener::MP_ALIAS
                : WebsiteSearchProductPriceIndexerListener::MP_ALIAS;
        }

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
                    'data_name' => $filterColumn
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
            ['data_name' => 'decimal.' . $sortColumn, 'type' => 'decimal']
        );
    }
}
