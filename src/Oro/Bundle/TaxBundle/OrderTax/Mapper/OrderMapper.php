<?php

namespace Oro\Bundle\TaxBundle\OrderTax\Mapper;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Selectable;
use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\TaxBundle\Event\ContextEventDispatcher;
use Oro\Bundle\TaxBundle\Mapper\TaxMapperInterface;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Provider\TaxationAddressProvider;

/**
 * Creates Taxable object from Order entity.
 */
class OrderMapper extends AbstractOrderMapper
{
    /** @var TaxMapperInterface */
    protected $orderLineItemMapper;

    /** @var PreloadingManager */
    private $preloadingManager;

    public function __construct(
        ContextEventDispatcher $contextEventDispatcher,
        TaxationAddressProvider $addressProvider,
        TaxMapperInterface $orderLineItemMapper,
        PreloadingManager $preloadingManager
    ) {
        parent::__construct($contextEventDispatcher, $addressProvider);

        $this->orderLineItemMapper = $orderLineItemMapper;
        $this->preloadingManager = $preloadingManager;
    }

    /**
     * {@inheritdoc}
     * @param Order $order
     */
    public function map($order)
    {
        $taxable = (new Taxable())
            ->setIdentifier($order->getId())
            ->setClassName(Order::class)
            ->setOrigin($this->addressProvider->getOriginAddress())
            ->setDestination($this->getDestinationAddress($order))
            ->setTaxationAddress($this->getTaxationAddress($order))
            ->setContext($this->getContext($order))
            ->setCurrency($order->getCurrency())
            ->setItems($this->mapLineItems($order->getLineItems()));//mapLineItems after getContext to preloadTaxCodes

        if ($order->getSubtotal()) {
            $taxable->setAmount($order->getSubtotal());
        }

        if ($order->getShippingCost()) {
            $taxable->setShippingCost($order->getShippingCost()->getValue());
        }

        return $taxable;
    }

    /**
     * @param Selectable|Collection|OrderLineItem[] $lineItems
     * @return \SplObjectStorage
     */
    protected function mapLineItems($lineItems)
    {
        $lineItems = $lineItems->toArray();
        $this->preloadingManager->preloadInEntities(
            $lineItems,
            [
                'product' => [
                    'taxCode' => [],
                ],
            ]
        );

        $storage = new \SplObjectStorage();

        array_walk(
            $lineItems,
            function (OrderLineItem $item) use ($storage) {
                $storage->attach($this->orderLineItemMapper->map($item));
            }
        );

        return $storage;
    }
}
