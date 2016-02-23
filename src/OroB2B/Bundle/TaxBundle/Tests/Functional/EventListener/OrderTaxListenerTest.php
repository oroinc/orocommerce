<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Functional\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

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

    protected function setUp()
    {
        $this->initClient();
        $this->doctrine = $this->getContainer()->get('doctrine');
    }

    public function testSaveOrderTaxValue()
    {
        $order = $this->createOrder();

        /** @var TaxValue $taxValue */
        $taxValue = $this->doctrine->getRepository('OroB2BTaxBundle:TaxValue')->findOneBy(
            [
                'entityClass' => 'OroB2B\Bundle\OrderBundle\Entity\Order',
                'entityId'    => $order->getId()
            ]
        );

        $this->assertNotNull($taxValue);
    }

    /**
     * @return Order
     */
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

        $entityManager = $this->doctrine->getManagerForClass('OroB2B\Bundle\OrderBundle\Entity\Order');

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

        $entityManager->persist($order);
        $entityManager->flush();
        return $order;
    }
}
