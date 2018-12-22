<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Api\Frontend\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\TaxBundle\Entity\TaxValue;
use Oro\Bundle\TaxBundle\EventListener\EntityTaxListener;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Model\TaxResultElement;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadOrderTaxesData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['@OroOrderBundle/Tests/Functional/Api/Frontend/DataFixtures/orders.yml'];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var EntityTaxListener $orderTaxValueListener */
        $orderTaxValueListener = $this->container->get('oro_tax.event_listener.order_tax');
        /** @var EntityTaxListener $lineItemTaxValueListener */
        $lineItemTaxValueListener = $this->container->get('oro_tax.event_listener.order_line_item_tax');
        $orderTaxValueListener->setEnabled(false);
        $lineItemTaxValueListener->setEnabled(false);
        try {
            $this->loadTaxes($manager);
        } finally {
            $orderTaxValueListener->setEnabled(true);
            $lineItemTaxValueListener->setEnabled(true);
        }
    }

    /**
     * @param ObjectManager $manager
     */
    private function loadTaxes(ObjectManager $manager)
    {
        /** @var Order $order1 */
        $order1 = $this->getReference('order1');
        $this->ensureTaxValueNotExists($manager, Order::class, $order1->getId());
        $order1TaxValue = new TaxValue();
        $order1TaxValue->setEntityClass(Order::class);
        $order1TaxValue->setEntityId($order1->getId());
        $order1TaxValue->setAddress('Test Address');
        $order1TaxValue->setResult($this->createOrderTaxValueResult($order1, 0.09, 0.01));
        $manager->persist($order1TaxValue);
        $this->setReference('order1_tax_value', $order1TaxValue);

        /** @var OrderLineItem $order1LineItem1 */
        $order1LineItem1 = $this->getReference('order1_line_item1');
        $this->ensureTaxValueNotExists($manager, OrderLineItem::class, $order1LineItem1->getId());
        $order1LineItem1TaxValue = new TaxValue();
        $order1LineItem1TaxValue->setEntityClass(OrderLineItem::class);
        $order1LineItem1TaxValue->setEntityId($order1LineItem1->getId());
        $order1LineItem1TaxValue->setAddress('Test Address');
        $order1LineItem1TaxValue->setResult($this->createLineItemTaxValueResult($order1LineItem1, 0.09, 0.01));
        $manager->persist($order1LineItem1TaxValue);
        $this->setReference('order1_line_item1_tax_value', $order1LineItem1TaxValue);

        /** @var OrderLineItem $order1LineItem2 */
        $order1LineItem2 = $this->getReference('order1_line_item2');
        $this->ensureTaxValueNotExists($manager, OrderLineItem::class, $order1LineItem2->getId());

        /** @var Order $order2 */
        $order2 = $this->getReference('order2');
        $this->ensureTaxValueNotExists($manager, Order::class, $order2->getId());
        $order2TaxValue = new TaxValue();
        $order2TaxValue->setEntityClass(Order::class);
        $order2TaxValue->setEntityId($order2->getId());
        $order2TaxValue->setAddress('Test Address');
        $order2TaxValue->setResult($this->createOrderTaxValueResult($order2, 0.05, 0.0));
        $manager->persist($order2TaxValue);
        $this->setReference('order2_tax_value', $order2TaxValue);

        /** @var OrderLineItem $order2LineItem1 */
        $order2LineItem1 = $this->getReference('order2_line_item1');
        $this->ensureTaxValueNotExists($manager, OrderLineItem::class, $order2LineItem1->getId());
        $order2LineItem1TaxValue = new TaxValue();
        $order2LineItem1TaxValue->setEntityClass(OrderLineItem::class);
        $order2LineItem1TaxValue->setEntityId($order2LineItem1->getId());
        $order2LineItem1TaxValue->setAddress('Test Address');
        $order2LineItem1TaxValue->setResult($this->createLineItemTaxValueResult($order2LineItem1, 0.05, 0.0));
        $manager->persist($order2LineItem1TaxValue);
        $this->setReference('order2_line_item1_tax_value', $order2LineItem1TaxValue);

        /** @var Order $order3 */
        $order3 = $this->getReference('order3');
        $this->ensureTaxValueNotExists($manager, Order::class, $order3->getId());

        /** @var OrderLineItem $order3LineItem1 */
        $order3LineItem1 = $this->getReference('order3_line_item1');
        $this->ensureTaxValueNotExists($manager, OrderLineItem::class, $order3LineItem1->getId());

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string        $entityClass
     * @param int           $entityId
     */
    private function ensureTaxValueNotExists(ObjectManager $manager, string $entityClass, int $entityId)
    {
        $taxValue = $manager->getRepository(TaxValue::class)
            ->findOneBy(['entityClass' => $entityClass, 'entityId' => $entityId]);
        if (null !== $taxValue) {
            $manager->remove($taxValue);
        }
    }

    /**
     * @param Order $order
     * @param float $taxRate
     * @param float $taxAdjustment
     *
     * @return Result
     */
    private function createOrderTaxValueResult(
        Order $order,
        float $taxRate,
        float $taxAdjustment
    ): Result {
        $total = $order->getTotal();
        $taxAmount = $total * $taxRate + $taxAdjustment;
        $subtotal = $order->getSubtotal();
        $shipping = $total - $subtotal;
        $shippingTaxAmount = $shipping * $taxRate + $taxAdjustment;

        return Result::jsonDeserialize([
            Result::SHIPPING => [
                ResultElement::INCLUDING_TAX => (string)($shipping + $shippingTaxAmount),
                ResultElement::EXCLUDING_TAX => (string)($shipping - $shippingTaxAmount),
                ResultElement::TAX_AMOUNT    => (string)$shippingTaxAmount,
                ResultElement::ADJUSTMENT    => (string)$taxAdjustment,
                ResultElement::CURRENCY      => $order->getCurrency()
            ],
            Result::TOTAL    => [
                ResultElement::INCLUDING_TAX => (string)($total + $taxAmount),
                ResultElement::EXCLUDING_TAX => (string)($total - $taxAmount),
                ResultElement::TAX_AMOUNT    => (string)$taxAmount,
                ResultElement::ADJUSTMENT    => (string)$taxAdjustment,
                ResultElement::CURRENCY      => $order->getCurrency()
            ],
            Result::TAXES    => [
                [
                    TaxResultElement::TAX            => 'TEST_SALES_TAX',
                    TaxResultElement::RATE           => (string)$taxRate,
                    TaxResultElement::TAXABLE_AMOUNT => (string)$total,
                    TaxResultElement::TAX_AMOUNT     => (string)$taxAmount,
                    TaxResultElement::CURRENCY       => $order->getCurrency()
                ]
            ]
        ]);
    }

    /**
     * @param OrderLineItem $lineItem
     * @param float         $taxRate
     * @param float         $taxAdjustment
     *
     * @return Result
     */
    private function createLineItemTaxValueResult(
        OrderLineItem $lineItem,
        float $taxRate,
        float $taxAdjustment
    ): Result {
        $quantity = $lineItem->getQuantity();
        $price = $lineItem->getValue();
        $taxAmount = $price * $taxRate + $taxAdjustment;
        $unitPrice = $price + $price * 0.1;
        $unitTaxAmount = $unitPrice * $taxRate + $taxAdjustment;

        return Result::jsonDeserialize([
            Result::UNIT  => [
                ResultElement::INCLUDING_TAX => (string)($unitPrice + $unitTaxAmount),
                ResultElement::EXCLUDING_TAX => (string)($unitPrice - $unitTaxAmount),
                ResultElement::TAX_AMOUNT    => (string)$unitTaxAmount,
                ResultElement::ADJUSTMENT    => (string)$taxAdjustment,
                ResultElement::CURRENCY      => $lineItem->getCurrency()
            ],
            Result::ROW   => [
                ResultElement::INCLUDING_TAX => (string)($price + $taxAmount),
                ResultElement::EXCLUDING_TAX => (string)($price - $taxAmount),
                ResultElement::TAX_AMOUNT    => (string)$taxAmount,
                ResultElement::ADJUSTMENT    => (string)$taxAdjustment,
                ResultElement::CURRENCY      => $lineItem->getCurrency()
            ],
            Result::TAXES => [
                [
                    TaxResultElement::TAX            => 'TEST_SALES_TAX',
                    TaxResultElement::RATE           => (string)$taxRate,
                    TaxResultElement::TAXABLE_AMOUNT => (string)($price * $quantity),
                    TaxResultElement::TAX_AMOUNT     => (string)($taxAmount * $quantity),
                    TaxResultElement::CURRENCY       => $lineItem->getCurrency()
                ]
            ]
        ]);
    }
}
