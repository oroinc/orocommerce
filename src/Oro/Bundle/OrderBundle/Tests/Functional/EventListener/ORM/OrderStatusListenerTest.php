<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\EventListener\ORM;

use Doctrine\Persistence\ManagerRegistry;
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

/**
 * @group CommunityEdition
 */
class OrderStatusListenerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var ConfigManager */
    private $configManager;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->doctrine = $this->getContainer()->get('doctrine');
        $this->configManager = self::getConfigManager();

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

    private function assertOrderStatus(string $expectedStatus): void
    {
        $order = $this->prepareOrderObject();
        $em = $this->doctrine->getManager();
        $em->persist($order);
        $em->flush();
        $order = $em->find(Order::class, $order->getId());
        self::assertEquals($expectedStatus, $order->getInternalStatus()->getId());
    }

    private function prepareOrderObject(): Order
    {
        /** @var User $user */
        $user = $this->getReference(LoadOrderUsers::ORDER_USER_1);
        if (!$user->getOrganization()) {
            $user->setOrganization($this->doctrine->getRepository(Organization::class)->findOneBy([]));
        }
        /** @var CustomerUser $customerUser */
        $customerUser = $this->doctrine->getRepository(CustomerUser::class)->findOneBy([]);
        /** @var PaymentTerm $paymentTerm */
        $paymentTerm = $this->doctrine->getRepository(PaymentTerm::class)->findOneBy([]);

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

    private function getDefaultWebsite(): Website
    {
        return $this->doctrine->getRepository(Website::class)->findOneBy(['default' => true]);
    }
}
