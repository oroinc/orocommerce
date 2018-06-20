<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\PricingBundle\Datagrid\Provider\CombinedProductPriceProvider;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;
use Symfony\Component\Translation\TranslatorInterface;

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
     * @var CombinedProductPriceProvider
     */
    private $combinedProductPriceProvider;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param ProductPriceScopeCriteriaRequestHandler $scopeCriteriaRequestHandler
     * @param UserCurrencyManager $currencyManager
     * @param CombinedProductPriceProvider $combinedProductPriceProvider
     * @param TranslatorInterface $translator
     */
    public function __construct(
        ProductPriceScopeCriteriaRequestHandler $scopeCriteriaRequestHandler,
        UserCurrencyManager $currencyManager,
        CombinedProductPriceProvider $combinedProductPriceProvider,
        TranslatorInterface $translator
    ) {
        $this->scopeCriteriaRequestHandler = $scopeCriteriaRequestHandler;
        $this->currencyManager = $currencyManager;
        $this->combinedProductPriceProvider = $combinedProductPriceProvider;
        $this->translator = $translator;
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
                    'data_name' => WebsiteSearchProductPriceIndexerListener::MP_ALIAS,
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
            ['data_name' => 'minimal_price_CPL_ID_CURRENCY', 'type' => 'decimal']
        );
    }
}
