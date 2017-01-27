<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\PricingBundle\Datagrid\Provider\CombinedProductPriceProviderInterface;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;
use Symfony\Component\Translation\TranslatorInterface;

class FrontendProductPriceDatagridListener
{
    const COLUMN_PRICES = 'prices';
    const COLUMN_MINIMAL_PRICE = 'minimal_price';
    const COLUMN_MINIMAL_PRICE_SORT = 'minimal_price_sort';

    /**
     * @var PriceListRequestHandler
     */
    private $priceListRequestHandler;

    /**
     * @var UserCurrencyManager
     */
    private $currencyManager;

    /**
     * @var CombinedPriceList
     */
    private $priceList;

    /**
     * @var CombinedProductPriceProviderInterface
     */
    private $combinedProductPriceProvider;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param PriceListRequestHandler $priceListRequestHandler
     * @param UserCurrencyManager $currencyManager
     * @param CombinedProductPriceProviderInterface $combinedProductPriceProvider
     * @param TranslatorInterface $translator
     */
    public function __construct(
        PriceListRequestHandler $priceListRequestHandler,
        UserCurrencyManager $currencyManager,
        CombinedProductPriceProviderInterface $combinedProductPriceProvider,
        TranslatorInterface $translator
    ) {
        $this->priceListRequestHandler = $priceListRequestHandler;
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
        if (count($records) === 0) {
            return;
        }

        $priceList = $this->getPriceList();
        if (!$priceList) {
            return;
        }

        $resultProductPrices = $this->combinedProductPriceProvider
            ->getCombinedPricesForProductsByPriceList($records, $priceList, $this->currencyManager->getUserCurrency());

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

    /**
     * @return CombinedPriceList
     */
    private function getPriceList()
    {
        if (!$this->priceList) {
            $this->priceList = $this->priceListRequestHandler->getPriceListByCustomer();
        }

        return $this->priceList;
    }
}
