<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Functional\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\TaxBundle\Entity\TaxValue;

/**
 * @dbIsolation
 */
class OrderTaxListenerTest extends WebTestCase
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var Order */
    protected $order;

    /** @var EntityManager */
    protected $entityManager;

    protected function setUp()
    {
        $this->initClient();
        $this->doctrine = $this->getContainer()->get('doctrine');
        $this->entityManager = $this->doctrine->getManagerForClass('OroB2B\Bundle\OrderBundle\Entity\Order');
    }

    public function testSaveOrderTaxValue()
    {
        $this->createOrder();

        $taxValue = $this->getTaxValue();
        $this->assertNotNull($taxValue);

        $this->removeTaxValue($taxValue);
        $this->updateOrder();

        $this->assertNotNull($this->getTaxValue());
    }

    protected function createOrder()
    {
        /** @var User $orderUser */
        $orderUser = $this->doctrine->getRepository('OroUserBundle:User')->findOneBy([]);
        if (!$orderUser->getOrganization()) {
            $orderUser->setOrganization(
                $this->doctrine->getRepository('OroOrganizationBundle:Organization')->findOneBy([])
            );
        }
        /** @var AccountUser $accountUser */
        $accountUser = $this->doctrine->getRepository('OroB2BAccountBundle:AccountUser')->findOneBy([]);

        /** @var PaymentTerm $paymentTerm */
        $paymentTerm = $this->doctrine->getRepository('OroB2BPaymentBundle:PaymentTerm')->findOneBy([]);

        $order = new Order();
        $order
            ->setIdentifier('tax_order')
            ->setOwner($orderUser)
            ->setOrganization($orderUser->getOrganization())
            ->setPaymentTerm($paymentTerm)
            ->setShipUntil(new \DateTime())
            ->setCurrency('EUR')
            ->setPoNumber('PO_NUM')
            ->setSubtotal('1500')
            ->setAccount($accountUser->getAccount())
            ->setAccountUser($accountUser);

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $this->order = $order;
    }

    protected function updateOrder()
    {
        $this->order
            ->setIdentifier('tax_order_updated')
            ->setSubtotal('1800');

        $this->entityManager->persist($this->order);
        $this->entityManager->flush();
    }

    /**
     * @return TaxValue $taxValue
     */
    protected function getTaxValue()
    {
        /** @var TaxValue $taxValue */
        $taxValue = $this->doctrine->getRepository('OroB2BTaxBundle:TaxValue')->findOneBy(
            [
                'entityClass' => 'OroB2B\Bundle\OrderBundle\Entity\Order',
                'entityId'    => $this->order->getId()
            ]
        );

        return $taxValue;
    }

    /**
     * @param TaxValue $taxValue
     */
    protected function removeTaxValue(TaxValue $taxValue)
    {
        $entityManager = $this->doctrine->getManagerForClass('OroB2B\Bundle\TaxBundle\Entity\TaxValue');

        $entityManager->remove($taxValue);
        $entityManager->flush();
    }
}
