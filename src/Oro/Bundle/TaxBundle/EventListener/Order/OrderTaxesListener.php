<?php

namespace Oro\Bundle\TaxBundle\EventListener\Order;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\EventListener\Order\MatchingPriceEventListener;
use Oro\Bundle\OrderBundle\Pricing\PriceMatcher;
use Oro\Bundle\TaxBundle\Model\AbstractResultElement;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Bundle\TaxBundle\Provider\TaxProviderInterface;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;

class OrderTaxesListener
{
    /** @var TaxProviderRegistry */
    protected $taxProviderRegistry;

    /** @var TaxationSettingsProvider */
    protected $taxationSettingsProvider;

    /** @var PriceMatcher */
    protected $priceMatcher;

    public function __construct(
        TaxProviderRegistry $taxProviderRegistry,
        TaxationSettingsProvider $taxationSettingsProvider,
        PriceMatcher $priceMatcher
    ) {
        $this->taxProviderRegistry = $taxProviderRegistry;
        $this->taxationSettingsProvider = $taxationSettingsProvider;
        $this->priceMatcher = $priceMatcher;
    }

    public function onOrderEvent(OrderEvent $event)
    {
        if (!$this->taxationSettingsProvider->isEnabled()) {
            return;
        }

        $order = $event->getOrder();
        $data = $event->getData();

        $this->addMatchedPriceToOrderLineItems($order, $data);

        $result = $this->getProvider()->getTax($order);
        $taxItems = array_map(
            function (Result $lineItem) {
                return [
                    'unit' => $lineItem->getUnit()->getArrayCopy(),
                    'row' => $lineItem->getRow()->getArrayCopy(),
                    'taxes' => array_map(
                        function (AbstractResultElement $item) {
                            return $item->getArrayCopy();
                        },
                        $lineItem->getTaxes()
                    ),
                ];
            },
            $result->getItems()
        );

        $data->offsetSet('taxItems', $taxItems);
    }

    protected function addMatchedPriceToOrderLineItems(Order $order, \ArrayAccess $data)
    {
        if (!$data->offsetExists(MatchingPriceEventListener::MATCHED_PRICES_KEY)) {
            return;
        }

        $matchedPrices = $data->offsetGet(MatchingPriceEventListener::MATCHED_PRICES_KEY);
        if (!$matchedPrices) {
            return;
        }

        $this->priceMatcher->fillMatchingPrices($order, $matchedPrices);
    }

    /**
     * @return TaxProviderInterface
     */
    private function getProvider()
    {
        return $this->taxProviderRegistry->getEnabledProvider();
    }
}
