<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\PricingBundle\Datagrid\Provider\ProductPriceProvider;
use Oro\Bundle\PricingBundle\Layout\DataProvider\FrontendProductPricesProvider;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Oro\Bundle\ProductBundle\Entity\Product;
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
    const COLUMN_SHOPPING_LIST_PRICES = 'shoppingListPrices';
    const COLUMN_MINIMAL_PRICE = 'minimal_price';
    const COLUMN_MINIMAL_PRICE_SORT = 'minimal_price_sort';

    private ProductPriceScopeCriteriaRequestHandler $scopeCriteriaRequestHandler;

    private UserCurrencyManager $currencyManager;

    private ProductPriceProvider $productPriceProvider;

    private TranslatorInterface $translator;

    private ?string $priceColumnNameFilter = null;

    private ?string $priceColumnNameSorter = null;

    private ?DoctrineHelper $doctrineHelper = null;

    private ?FrontendProductPricesProvider $frontendProductPricesProvider = null;

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

    public function setDoctrineHelper(DoctrineHelper $doctrineHelper): void
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    public function setFrontendProductPricesProvider(FrontendProductPricesProvider $frontendProductPricesProvider): void
    {
        $this->frontendProductPricesProvider = $frontendProductPricesProvider;
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

        $products = $this->getProducts($records);
        $shoppingListPrices = $this->frontendProductPricesProvider instanceof FrontendProductPricesProvider
            ? $this->frontendProductPricesProvider->getShoppingListPricesByProducts($products)
            : [];

        foreach ($records as $record) {
            $productId = $record->getValue('id');
            if (array_key_exists($productId, $resultProductPrices)) {
                $record->addData([static::COLUMN_PRICES => $resultProductPrices[$productId]]);
            } else {
                $record->addData([static::COLUMN_PRICES => []]);
            }

            if (array_key_exists($productId, $shoppingListPrices)) {
                $record->addData([self::COLUMN_SHOPPING_LIST_PRICES => $shoppingListPrices[$productId]]);
            } else {
                $record->addData([self::COLUMN_SHOPPING_LIST_PRICES => []]);
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
                self::COLUMN_SHOPPING_LIST_PRICES => [
                    'type' => 'field',
                    'frontend_type' => PropertyInterface::TYPE_ROW_ARRAY
                ]
            ]
        );

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

    /**
     * @param ResultRecordInterface[] $productRecords
     * @return Product[]
     */
    private function getProducts(array $productRecords): array
    {
        if (!$this->doctrineHelper instanceof DoctrineHelper) {
            return [];
        }

        return array_map(
            function (ResultRecordInterface $record) {
                return $this->doctrineHelper->getEntityReference(Product::class, $record->getValue('id'));
            },
            $productRecords
        );
    }
}
