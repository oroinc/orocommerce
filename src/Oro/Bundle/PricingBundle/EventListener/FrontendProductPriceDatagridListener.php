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

class FrontendProductPriceDatagridListener
{
    const COLUMN_PRICES = 'prices';

    /**
     * @var PriceListRequestHandler
     */
    protected $priceListRequestHandler;

    /**
     * @var UserCurrencyManager
     */
    protected $currencyManager;

    /**
     * @var CombinedPriceList
     */
    protected $priceList;

    /**
     * @var CombinedProductPriceProviderInterface
     */
    protected $combinedProductPriceProvider;

    /**
     * @param PriceListRequestHandler               $priceListRequestHandler
     * @param UserCurrencyManager                   $currencyManager
     * @param CombinedProductPriceProviderInterface $combinedProductPriceProvider
     */
    public function __construct(
        PriceListRequestHandler $priceListRequestHandler,
        UserCurrencyManager $currencyManager,
        CombinedProductPriceProviderInterface $combinedProductPriceProvider
    ) {
        $this->priceListRequestHandler      = $priceListRequestHandler;
        $this->currencyManager              = $currencyManager;
        $this->combinedProductPriceProvider = $combinedProductPriceProvider;
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
        $config   = $event->getConfig();
        $currency = $this->currencyManager->getUserCurrency();
        if (!$currency) {
            return;
        }

        $config->offsetAddToArrayByPath(
            '[properties]',
            [
                self::COLUMN_PRICES => [
                    'type'          => 'field',
                    'frontend_type' => PropertyInterface::TYPE_ROW_ARRAY
                ]
            ]
        );
    }

    /**
     * @return CombinedPriceList
     */
    protected function getPriceList()
    {
        if (!$this->priceList) {
            $this->priceList = $this->priceListRequestHandler->getPriceListByAccount();
        }

        return $this->priceList;
    }
}
