<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Process;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerAddresses;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserAddresses;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderUsers;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Tests\Functional\Process\AbstractProcessTest;

/**
 * @group CommunityEdition
 */
class ExpireOrdersProcessTest extends AbstractProcessTest
{
    use ConfigManagerAwareTestTrait;

    /** @var ProcessDefinition */
    private $processDefinition;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var ConfigManager */
    private $configManager;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->processDefinition = $this->getContainer()
            ->get('doctrine')
            ->getRepository(ProcessDefinition::class)
            ->findOneBy(['name' => 'expire_orders']);

        $this->doctrine = $this->getContainer()->get('doctrine');
        $this->configManager = self::getConfigManager();

        $this->loadFixtures([
            LoadCustomers::class,
            LoadCustomerAddresses::class,
            LoadCustomerUserData::class,
            LoadCustomerUserAddresses::class,
            LoadOrders::class,
        ]);

        /** @var Connection $connection */
        $connection = $this->getContainer()->get('doctrine')->getConnection();

        $qb = $connection->createQueryBuilder();
        $qb->delete('oro_config_value')
            ->andWhere($qb->expr()->eq('section', 'oro_order'));
    }

    public function testProcessDefinition()
    {
        $this->assertNotNull($this->processDefinition);
        $this->assertNotEmpty($this->processDefinition->getActionsConfiguration());
        $this->assertTrue($this->processDefinition->isEnabled());
        $this->assertEquals(Order::class, $this->processDefinition->getRelatedEntity());
    }

    public function testProcessTrigger()
    {
        $processTriggers = $this->getContainer()
            ->get('doctrine')
            ->getRepository(ProcessTrigger::class)
            ->findBy(['definition' => $this->processDefinition]);

        $this->assertNotEmpty($processTriggers);
    }

    public function testExecuteWithoutDNSL()
    {
        $this->configureMockManager();

        $order = $this->prepareOrderObject();
        $internalStatus = $order->getInternalStatus();
        $this->executeProcess($this->processDefinition);
        $order = $this->reloadOrder($order);
        $this->assertEquals($internalStatus, $order->getInternalStatus());
    }

    public function testExecuteWithDisabledAutomation()
    {
        $this->configureMockManager(false);

        $order = $this->prepareOrderObject((new \DateTime())->modify('-1 day'));

        $internalStatus = $order->getInternalStatus();
        $this->executeProcess($this->processDefinition);
        $order = $this->reloadOrder($order);
        $this->assertEquals($internalStatus->getId(), $order->getInternalStatus()->getId());
    }

    public function testExecuteDefault()
    {
        $this->configureMockManager();

        $order = $this->prepareOrderObject((new \DateTime())->modify('-1 day'));
        $this->executeProcess($this->processDefinition);
        $order = $this->reloadOrder($order);
        $this->assertEquals(
            $this->getOrderInternalStatusById(OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED)->getId(),
            $order->getInternalStatus()->getId()
        );
    }

    public function testExecuteWithCurrentDateForDNSL()
    {
        $this->configureMockManager();

        $order = $this->prepareOrderObject((new \DateTime()));
        $internalStatus = $order->getInternalStatus();
        $this->executeProcess($this->processDefinition);
        $order = $this->reloadOrder($order);
        $this->assertEquals(
            $internalStatus->getId(),
            $order->getInternalStatus()->getId()
        );
    }

    public function testExecuteWithOverriddenTargetStatus()
    {
        $this->configureMockManager(true, [OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN], 'closed');

        $order = $this->prepareOrderObject((new \DateTime())->modify('-1 day'));
        $this->executeProcess($this->processDefinition);
        $order = $this->reloadOrder($order);
        $this->assertEquals(
            $this->getOrderInternalStatusById('closed')->getId(),
            $order->getInternalStatus()->getId()
        );
    }

    public function testExecuteWithOverriddenApplicableStatuses()
    {
        $this->configureMockManager(true, ['closed']);

        $order1 = $this->prepareOrderObject((new \DateTime())->modify('-1 day'));
        $order2 = $this->prepareOrderObject((new \DateTime())->modify('-1 day'), 'closed');
        $this->executeProcess($this->processDefinition);
        $order1 = $this->reloadOrder($order1);
        $this->assertEquals(
            $this->getOrderInternalStatusById(OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN)->getId(),
            $order1->getInternalStatus()->getId()
        );
        $order2 = $this->reloadOrder($order2);
        $this->assertEquals(
            $this->getOrderInternalStatusById(OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED)->getId(),
            $order2->getInternalStatus()->getId()
        );
    }

    private function configureMockManager(
        bool $enabled = true,
        array $statuses = [OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN],
        string $target = OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED
    ): void {
        $this->configManager->set('oro_order.order_automation_enable_cancellation', $enabled);
        $this->configManager->set('oro_order.order_automation_applicable_statuses', $statuses);
        $this->configManager->set('oro_order.order_automation_target_status', $target);
    }

    /**
     * {@inheritdoc}
     */
    protected function getProcessHandler()
    {
        return $this->getContainer()->get('oro_workflow.process.process_handler');
    }

    private function prepareOrderObject(
        \DateTime $doNotShipLater = null,
        string $internalStatus = OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN
    ): Order {
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
            ->setCurrency('USD')
            ->setSubtotal(100)
            ->setShipUntil($doNotShipLater)
            ->setTotal(100)
            ->setCustomer($customerUser->getCustomer())
            ->setWebsite($this->getDefaultWebsite())
            ->setCustomerUser($customerUser)
            ->setInternalStatus($this->getOrderInternalStatusById($internalStatus));

        $this->getContainer()->get('oro_payment_term.provider.payment_term_association')
            ->setPaymentTerm($order, $paymentTerm);

        $em = $this->doctrine->getManager();
        $em->persist($order);
        $em->flush();

        return $this->reloadOrder($order);
    }

    private function getOrderInternalStatusById(string $id): AbstractEnumValue
    {
        $className = ExtendHelper::buildEnumValueClassName(Order::INTERNAL_STATUS_CODE);

        return $this->doctrine->getManagerForClass($className)->getRepository($className)->find($id);
    }

    private function reloadOrder(Order $order): Order
    {
        return $this->doctrine->getRepository(Order::class)
            ->findOneBy(['id' => $order->getId()]);
    }

    private function getDefaultWebsite(): Website
    {
        return $this->doctrine->getRepository(Website::class)->findOneBy(['default' => true]);
    }
}
