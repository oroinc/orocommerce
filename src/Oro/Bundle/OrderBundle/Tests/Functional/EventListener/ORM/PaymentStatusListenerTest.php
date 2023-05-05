<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\EventListener\ORM;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProvider;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class PaymentStatusListenerTest extends WebTestCase
{
    protected ManagerRegistry $managerRegistry;
    private PaymentStatusProvider $paymentStatusProvider;
    private PaymentTransactionProvider $paymentTransactionProvider;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->managerRegistry = $this->getContainer()->get('doctrine');
        $this->paymentStatusProvider = $this->getContainer()->get('oro_payment.provider.payment_status');
        $this->paymentTransactionProvider = $this->getContainer()->get('oro_payment.provider.payment_transaction');

        $this->loadFixtures([
            LoadCustomerUserData::class
        ]);
    }

    public function testPreUpdate()
    {
        $subOrder1 = $this->prepareOrderObject(50, '#42-1');
        $subOrder2 = $this->prepareOrderObject(50, '#42-2');

        $order = $this->prepareOrderObject(100, '#42');
        $order->addSubOrder($subOrder1);
        $order->addSubOrder($subOrder2);

        $em = $this->managerRegistry->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();

        $paymentTransaction1 = $this->addPaymnentTransaction($subOrder1, PaymentMethodInterface::AUTHORIZE, true);
        $paymentTransaction2 = $this->addPaymnentTransaction($subOrder2, PaymentMethodInterface::AUTHORIZE, false);
        $em->persist($paymentTransaction1);
        $em->persist($paymentTransaction2);
        $em->flush();

        $this->assertEquals(
            PaymentStatusProvider::AUTHORIZED,
            $this->paymentStatusProvider->getPaymentStatus($subOrder1)
        );

        $this->assertEquals(
            PaymentStatusProvider::PENDING,
            $this->paymentStatusProvider->getPaymentStatus($subOrder2)
        );

        $this->assertEquals(
            PaymentStatusProvider::AUTHORIZED_PARTIALLY,
            $this->paymentStatusProvider->getPaymentStatus($order)
        );
    }

    protected function prepareOrderObject(float $total, string $poNumber): Order
    {
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);
        if (!$user->getOrganization()) {
            $user->setOrganization($this->managerRegistry->getRepository(Organization::class)->findOneBy([]));
        }
        /** @var CustomerUser $customerUser */
        $customerUser = $this->managerRegistry->getRepository(CustomerUser::class)->findOneBy([]);

        $order = new Order();
        $order
            ->setOwner($user)
            ->setPoNumber($poNumber)
            ->setOrganization($user->getOrganization())
            ->setCurrency('USD')
            ->setSubtotal($total)
            ->setTotal($total)
            ->setCustomer($customerUser->getCustomer())
            ->setWebsite($this->getDefaultWebsite())
            ->setCustomerUser($customerUser);

        return $order;
    }

    private function addPaymnentTransaction(
        Order $order,
        string $transactionType,
        bool $transactionStatus
    ): PaymentTransaction {
        $transaction = $this->paymentTransactionProvider->createPaymentTransaction('pm1', $transactionType, $order);
        $transaction->setSuccessful($transactionStatus);
        $transaction->setActive(true);
        $transaction->setAmount($order->getTotal());
        $transaction->setCurrency($order->getCurrency());

        return $transaction;
    }

    protected function getDefaultWebsite(): Website
    {
        return $this->managerRegistry->getRepository(Website::class)->findOneBy(['default' => true]);
    }
}
