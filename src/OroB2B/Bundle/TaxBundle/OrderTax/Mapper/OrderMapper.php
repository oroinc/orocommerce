<?php

namespace OroB2B\Bundle\TaxBundle\OrderTax\Mapper;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\TaxBundle\Mapper\TaxMapperInterface;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

class OrderMapper implements TaxMapperInterface
{
    const PROCESSING_CLASS_NAME = 'OroB2B\Bundle\OrderBundle\Entity\Order';

    /**
     * @var OrderLineItemMapper
     */
    protected $orderLineItemMapper;

    /**
     * @var TaxationSettingsProvider
     */
    protected $settingsProvider;

    /**
     * @param OrderLineItemMapper $orderLineItemMapper
     * @param TaxationSettingsProvider $settingsProvider
     */
    public function __construct(OrderLineItemMapper $orderLineItemMapper, TaxationSettingsProvider $settingsProvider)
    {
        $this->orderLineItemMapper = $orderLineItemMapper;
        $this->settingsProvider = $settingsProvider;
    }

    /**
     * {@inheritdoc}
     * @param Order $order
     */
    public function map($order)
    {
        $taxable = (new Taxable())
            ->setAmount($order->getSubtotal())
            ->setIdentifier($order->getId())
            ->setItems($this->mapLineItems($order->getLineItems()))
            ->setDestination(
                $this->settingsProvider->isBillingAddressDestination() ?
                    $order->getBillingAddress() : $order->getShippingAddress()
            )
            ->setOrigin($this->settingsProvider->getOrigin());

        return $taxable;
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessingClassName()
    {
        return self::PROCESSING_CLASS_NAME;
    }

    /**
     * @param Collection|OrderLineItem[] $lineItems
     * @return ArrayCollection
     */
    protected function mapLineItems($lineItems)
    {
        $taxableItems = $lineItems->map(
            function (OrderLineItem $item) {
                return $this->orderLineItemMapper->map($item);
            }
        );

        return $taxableItems;
    }
}
