<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\EventListener\ORM;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerAddresses;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserAddresses;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderUsers;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Bridge\Doctrine\ManagerRegistry;

/**
 * @group CommunityEdition
 */
class OrderStatusListenerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    /** @var ManagerRegistry */
    protected $managerRegistry;

    /** @var ConfigManager */
    protected $configManager;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->managerRegistry = $this->getContainer()->get('doctrine');
        $this->configManager = self::getConfigManager('global');

        $this->loadFixtures([
            LoadCustomers::class,
            LoadCustomerAddresses::class,
            LoadCustomerUserData::class,
            LoadCustomerUserAddresses::class,
            LoadOrders::class,
        ]);
    }

    public function testPrePersistDefaultStatus()
    {
        $this->configManager->reset('oro_order.order_creation_new_internal_order_status');

        $this->assertOrderStatus(OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN);
    }

    public function testPrePersistOverriddenStatus()
    {
        $this->configManager->set(
            'oro_order.order_creation_new_internal_order_status',
            OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED
        );

        $this->assertOrderStatus(OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED);
    }

    /**
     * @param string $expectedStatus
     */
    protected function assertOrderStatus($expectedStatus)
    {
        $order = $this->prepareOrderObject();
        $em = $this->managerRegistry->getManager();
        $em->persist($order);
        $em->flush();
        $order = $em->find(Order::class, $order->getId());
        self::assertEquals($expectedStatus, $order->getInternalStatus()->getId());
    }

    /**
     * @return Order
     */
    protected function prepareOrderObject()
    {
        /** @var User $user */
        $user = $this->getReference(LoadOrderUsers::ORDER_USER_1);
        if (!$user->getOrganization()) {
            $user->setOrganization($this->managerRegistry->getRepository(Organization::class)->findOneBy([]));
        }
        /** @var CustomerUser $customerUser */
        $customerUser = $this->managerRegistry->getRepository(CustomerUser::class)->findOneBy([]);
        /** @var PaymentTerm $paymentTerm */
        $paymentTerm = $this->managerRegistry->getRepository(PaymentTerm::class)->findOneBy([]);

        $order = new Order();
        $order
            ->setOwner($user)
            ->setOrganization($user->getOrganization())
            ->setShipUntil(new \DateTime())
            ->setCurrency('USD')
            ->setSubtotal(100)
            ->setTotal(100)
            ->setCustomer($customerUser->getCustomer())
            ->setWebsite($this->getDefaultWebsite())
            ->setCustomerUser($customerUser);

        $this->getContainer()->get('oro_payment_term.provider.payment_term_association')
            ->setPaymentTerm($order, $paymentTerm);

        return $order;
    }

    /**
     * @return Website
     */
    protected function getDefaultWebsite()
    {
        return $this->managerRegistry->getRepository(Website::class)->findOneBy(['default' => true]);
    }
}
