<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Model\Order;

use Oro\Bundle\ApruveBundle\Apruve\Model\AbstractApruveEntityFactory;
use Oro\Bundle\ApruveBundle\Apruve\Model\LineItem\ApruveLineItem;
use Oro\Bundle\ApruveBundle\Apruve\Model\LineItem\ApruveLineItemFactoryInterface;
use Oro\Bundle\ApruveBundle\Apruve\Provider\SupportedCurrenciesProviderInterface;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;

class ApruveOrderFactory extends AbstractApruveEntityFactory implements ApruveOrderFactoryInterface
{
    /**
     * @var ApruveLineItemFactoryInterface
     */
    private $apruveLineItemFactory;

    /**
     * @param SupportedCurrenciesProviderInterface $supportedCurrenciesProvider
     * @param ApruveLineItemFactoryInterface $apruveLineItemFactory
     */
    public function __construct(
        SupportedCurrenciesProviderInterface $supportedCurrenciesProvider,
        ApruveLineItemFactoryInterface $apruveLineItemFactory
    ) {
        parent::__construct($supportedCurrenciesProvider);

        $this->apruveLineItemFactory = $apruveLineItemFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function createFromOrder(Order $order, ApruveConfigInterface $config)
    {
        $data = [
            ApruveOrder::MERCHANT_ORDER_ID => $order->getId(),
            ApruveOrder::MERCHANT_ID => $config->getMerchantId(),
            ApruveOrder::AMOUNT_CENTS => $this->normalizeAmount($order->getTotal()),
            ApruveOrder::SHIPPING_CENTS => $this->getAmountFromPrice($order->getShippingCost()),
            ApruveOrder::CURRENCY => $this->getCurrency($order->getCurrency()),
            ApruveOrder::LINE_ITEMS => $this->getLineItems($order->getLineItems()),
        ];

        return new ApruveOrder($data);
    }


    /**
     * @param OrderLineItem[] $lineItems
     *
     * @return ApruveLineItem[]
     */
    protected function getLineItems($lineItems)
    {
        $apruveLineItems = [];
        foreach ($lineItems as $lineItem) {
            $apruveLineItems[] = $this->apruveLineItemFactory->createFromOrderLineItem($lineItem);
        }

        return $apruveLineItems;
    }
}
