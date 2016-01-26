<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures;

use Brick\Math\BigDecimal;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Util\ClassUtils;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use OroB2B\Bundle\TaxBundle\Entity\Tax;
use OroB2B\Bundle\TaxBundle\Entity\TaxApply;
use OroB2B\Bundle\TaxBundle\Entity\TaxValue;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;

class LoadTaxValues extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders',
            __NAMESPACE__ . '\LoadTaxes',
        ];
    }

    /** {@inheritdoc} */
    public function load(ObjectManager $manager)
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        /** @var Tax $tax */
        $tax = $this->getReference(LoadTaxes::REFERENCE_PREFIX . '.' . LoadTaxes::TAX_1);

        $taxAmount = (string)BigDecimal::of(LoadOrders::SUBTOTAL)->multipliedBy($tax->getRate());

        $taxValue = new TaxValue();
        $taxValue
            ->setEntityId($order->getId())
            ->setEntityClass(ClassUtils::getClass($order))
            ->setResult(
                new Result(
                    [
                        Result::TOTAL => ResultElement::create(
                            LoadOrders::SUBTOTAL + $taxAmount,
                            LoadOrders::SUBTOTAL,
                            $taxAmount
                        ),
                    ]
                )
            )
            ->setAddress('Address');

        $manager->persist($taxValue);

        $taxApply = new TaxApply();
        $taxApply
            ->setRate($tax->getRate())
            ->setTax($tax)
            ->setTaxableAmount(LoadOrders::SUBTOTAL)
            ->setTaxValue($taxValue)
            ->setTaxAmount($taxAmount);

        $manager->persist($taxApply);
        $manager->flush();
    }
}
