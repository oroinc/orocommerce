<?php

namespace OroB2B\Bundle\TaxBundle\OrderTax\Mapper;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Common\Util\ClassUtils;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\TaxBundle\Mapper\TaxMapperInterface;
use OroB2B\Bundle\TaxBundle\Model\Taxable;

class OrderMapper extends AbstractOrderMapper
{
    const PROCESSING_CLASS_NAME = 'OroB2B\Bundle\OrderBundle\Entity\Order';

    /**
     * @var OrderLineItemMapper
     */
    protected $orderLineItemMapper;

    /**
     * {@inheritdoc}
     * @param Order $order
     */
    public function map($order)
    {
        $taxable = (new Taxable())
            ->setAmount($order->getSubtotal())
            ->setIdentifier($order->getId())
            ->setClassName(ClassUtils::getClass($order))
            ->setItems($this->mapLineItems($order->getLineItems()))
            ->setDestination($this->getOrderAddress($order))
            ->setOrigin($this->addressProvider->getOriginAddress())
            ->setContext($this->getContext($order))
            ->setCurrency($order->getCurrency());

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
     * @param Selectable|Collection|OrderLineItem[] $lineItems
     * @return \SplObjectStorage
     */
    protected function mapLineItems($lineItems)
    {
        $storage = new \SplObjectStorage();

        $criteria = Criteria::create();
        $criteria->orderBy(['id' => Criteria::ASC]);

        $lineItems
            ->matching($criteria)
            ->map(
                function (OrderLineItem $item) use ($storage) {
                    $storage->attach($this->orderLineItemMapper->map($item));
                }
            );

        return $storage;
    }

    /**
     * @param TaxMapperInterface $orderLineItemMapper
     */
    public function setOrderLineItemMapper(TaxMapperInterface $orderLineItemMapper)
    {
        $this->orderLineItemMapper = $orderLineItemMapper;
    }
}
