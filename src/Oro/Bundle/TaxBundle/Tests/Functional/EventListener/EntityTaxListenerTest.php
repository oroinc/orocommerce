<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\PaymentBundle\Entity\PaymentTerm;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\TaxBundle\Entity\TaxValue;

/**
 * @dbIsolation
 */
class OrderTaxListenerTest extends WebTestCase
{
    const ORDER_CLASS = 'Oro\Bundle\OrderBundle\Entity\Order';
    const TAX_VALUE_CLASS = 'Oro\Bundle\TaxBundle\Entity\TaxValue';

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var EntityManager */
    protected $orderEm;

    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->doctrine = $this->getContainer()->get('doctrine');
        $this->orderEm = $this->doctrine->getManagerForClass(self::ORDER_CLASS);
    }

    protected function tearDown()
    {
        unset($this->orderEm, $this->doctrine);
    }

    public function testSaveOrderTaxValue()
    {
        $order = $this->createOrder();

        $taxValue = $this->getTaxValue($order);
        $this->assertNotNull($taxValue);
        $this->removeTaxValue($taxValue);
        $this->assertNull($this->getTaxValue($order));

        $this->updateOrder($order);
        $this->assertNotNull($this->getTaxValue($order));
    }

    public function testSaveTwoNewOrders()
    {
        $order1 = $this->createOrder(false);
        $order2 = $this->createOrder();

        $taxValue1 = $this->getTaxValue($order1);
        $this->assertNotNull($taxValue1);

        $taxValue2 = $this->getTaxValue($order2);
        $this->assertNotNull($taxValue2);
    }

    public function testRemoveOrderShouldRemoveTaxValue()
    {
        $order1 = $this->createOrder(false);
        $order2 = $this->createOrder(false);
        $order3 = $this->createOrder();

        $this->assertNotNull($this->getTaxValue($order1));
        $this->assertNotNull($this->getTaxValue($order2));

        $this->removeOrder($order1);

        $this->assertNull($this->getTaxValue($order1));
        $this->assertNotNull($this->getTaxValue($order2));
        $this->assertNotNull($this->getTaxValue($order3));
    }

    /**
     * @param bool $flush
     * @return Order
     */
    protected function createOrder($flush = true)
    {
        /** @var User $orderUser */
        $orderUser = $this->doctrine->getRepository('OroUserBundle:User')->findOneBy([]);
        if (!$orderUser->getOrganization()) {
            $orderUser->setOrganization(
                $this->doctrine->getRepository('OroOrganizationBundle:Organization')->findOneBy([])
            );
        }
        /** @var AccountUser $accountUser */
        $accountUser = $this->doctrine->getRepository('OroAccountBundle:AccountUser')->findOneBy([]);

        /** @var PaymentTerm $paymentTerm */
        $paymentTerm = $this->doctrine->getRepository('OroPaymentBundle:PaymentTerm')->findOneBy([]);

        $order = new Order();
        $order
            ->setIdentifier(uniqid('identifier_', true))
            ->setOwner($orderUser)
            ->setOrganization($orderUser->getOrganization())
            ->setPaymentTerm($paymentTerm)
            ->setShipUntil(new \DateTime())
            ->setCurrency('EUR')
            ->setPoNumber('PO_NUM')
            ->setSubtotal('1500')
            ->setAccount($accountUser->getAccount())
            ->setAccountUser($accountUser);

        $this->orderEm->persist($order);

        if ($flush) {
            $this->orderEm->flush();
        }

        return $order;
    }

    /**
     * @param Order $order
     */
    protected function updateOrder(Order $order)
    {
        $order
            ->setIdentifier(uniqid('identifier_', true))
            ->setSubtotal('1800');

        $this->orderEm->persist($order);
        $this->orderEm->flush();
    }

    /**
     * @param Order $order
     * @return TaxValue $taxValue
     */
    protected function getTaxValue(Order $order)
    {
        /** @var TaxValue $taxValue */
        $taxValue = $this->doctrine->getRepository(self::TAX_VALUE_CLASS)->findOneBy(
            [
                'entityClass' => self::ORDER_CLASS,
                'entityId' => $order->getId()
            ]
        );

        return $taxValue;
    }

    /**
     * @param TaxValue $taxValue
     * @param bool $flush
     */
    protected function removeTaxValue(TaxValue $taxValue, $flush = true)
    {
        $em = $this->doctrine->getManagerForClass(self::TAX_VALUE_CLASS);
        $em->remove($taxValue);
        if ($flush) {
            $em->flush();
        }
    }

    /**
     * @param $entity
     * @param bool $flush
     */
    protected function removeOrder($entity, $flush = true)
    {
        $em = $this->orderEm;
        $em->remove($entity);
        if ($flush) {
            $em->flush();
        }
    }
}
