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
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderUsers;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;

/**
 * @group CommunityEdition
 */
class ExpireOrdersProcessTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private ManagerRegistry $doctrine;
    private ProcessDefinition $processDefinition;
    private ConfigManager $configManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        self::assertEquals(
            ExtendHelper::buildEnumOptionId(
                Order::INTERNAL_STATUS_CODE,
                OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN
            ),
            self::getConfigManager(null)->get('oro_order.order_creation_new_internal_order_status')
        );

        $this->doctrine = self::getContainer()->get('doctrine');
        $this->processDefinition = $this->doctrine->getRepository(ProcessDefinition::class)
            ->findOneBy(['name' => 'expire_orders']);

        $this->configManager = self::getConfigManager();

        $this->loadFixtures([
            LoadCustomers::class,
            LoadCustomerAddresses::class,
            LoadCustomerUserData::class,
            LoadCustomerUserAddresses::class,
            LoadOrders::class
        ]);

        /** @var Connection $connection */
        $connection = $this->doctrine->getConnection();
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
        $processTriggers = $this->doctrine->getRepository(ProcessTrigger::class)
            ->findBy(['definition' => $this->processDefinition]);

        $this->assertNotEmpty($processTriggers);
    }

    public function testExecuteWithoutDNSL()
    {
        $order = $this->prepareOrderObject();
        $internalStatus = $order->getInternalStatus();

        $this->initializeConfigs();
        $this->executeProcess($this->processDefinition);

        $order = $this->reloadOrder($order);
        $this->assertEquals($internalStatus, $order->getInternalStatus());
    }

    public function testExecuteWithDisabledAutomation()
    {
        $order = $this->prepareOrderObject((new \DateTime())->modify('-1 day'));
        $internalStatus = $order->getInternalStatus();

        $this->initializeConfigs(false);
        $this->executeProcess($this->processDefinition);

        $order = $this->reloadOrder($order);
        $this->assertEquals($internalStatus->getId(), $order->getInternalStatus()->getId());
    }

    public function testExecuteDefault()
    {
        $order = $this->prepareOrderObject((new \DateTime())->modify('-1 day'));

        $this->initializeConfigs();
        $this->executeProcess($this->processDefinition);

        $order = $this->reloadOrder($order);
        $this->assertEquals(
            $this->getOrderInternalStatusById(OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED)->getId(),
            $order->getInternalStatus()->getId()
        );
    }

    public function testExecuteWithCurrentDateForDNSL()
    {
        $order = $this->prepareOrderObject((new \DateTime()));
        $internalStatus = $order->getInternalStatus();

        $this->initializeConfigs();
        $this->executeProcess($this->processDefinition);

        $order = $this->reloadOrder($order);
        $this->assertEquals(
            $internalStatus->getId(),
            $order->getInternalStatus()->getId()
        );
    }

    public function testExecuteWithOverriddenTargetStatus()
    {
        $order = $this->prepareOrderObject((new \DateTime())->modify('-1 day'));

        $this->initializeConfigs(
            true,
            [OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN],
            OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED
        );
        $this->executeProcess($this->processDefinition);

        $order = $this->reloadOrder($order);
        $this->assertEquals(
            $this->getOrderInternalStatusById('closed')->getId(),
            $order->getInternalStatus()->getId()
        );
    }

    public function testExecuteWithOverriddenApplicableStatuses()
    {
        $order1 = $this->prepareOrderObject((new \DateTime())->modify('-1 day'));
        $order2 = $this->prepareOrderObject(
            (new \DateTime())->modify('-1 day'),
            OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED
        );

        $this->initializeConfigs(true, [OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED]);
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

    private function executeProcess(ProcessDefinition $definition): void
    {
        $trigger = new ProcessTrigger();
        $trigger->setId($definition->getName());
        $trigger->setDefinition($definition);
        $trigger->setEvent(ProcessTrigger::EVENT_CREATE);

        self::getContainer()->get('oro_workflow.process.process_handler')
            ->handleTrigger($trigger, new ProcessData());
    }

    private function initializeConfigs(
        bool $enabled = true,
        array $statuses = [
            OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN
        ],
        string $target = OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED
    ): void {
        $this->configManager->set('oro_order.order_automation_enable_cancellation', $enabled);
        $this->configManager->set(
            'oro_order.order_automation_applicable_statuses',
            ExtendHelper::mapToEnumOptionIds(Order::INTERNAL_STATUS_CODE, $statuses)
        );
        $this->configManager->set(
            'oro_order.order_automation_target_status',
            ExtendHelper::buildEnumOptionId(
                Order::INTERNAL_STATUS_CODE,
                $target
            )
        );
    }

    private function prepareOrderObject(
        ?\DateTime $doNotShipLater = null,
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
        $order->setOwner($user);
        $order->setOrganization($user->getOrganization());
        $order->setCurrency('USD');
        $order->setSubtotal(100);
        $order->setShipUntil($doNotShipLater);
        $order->setTotal(100);
        $order->setCustomer($customerUser->getCustomer());
        $order->setWebsite($this->getDefaultWebsite());
        $order->setCustomerUser($customerUser);
        $order->setInternalStatus($this->getOrderInternalStatusById($internalStatus));

        self::getContainer()->get('oro_payment_term.provider.payment_term_association')
            ->setPaymentTerm($order, $paymentTerm);

        $em = $this->doctrine->getManager();
        $em->persist($order);
        $em->flush();

        return $this->reloadOrder($order);
    }

    private function getOrderInternalStatusById(string $id): EnumOptionInterface
    {
        return $this->doctrine->getManagerForClass(EnumOption::class)->getRepository(EnumOption::class)
            ->find(ExtendHelper::buildEnumOptionId(Order::INTERNAL_STATUS_CODE, $id));
    }

    private function reloadOrder(Order $order): Order
    {
        return $this->doctrine->getRepository(Order::class)->findOneBy(['id' => $order->getId()]);
    }

    private function getDefaultWebsite(): Website
    {
        return $this->doctrine->getRepository(Website::class)->findOneBy(['default' => true]);
    }
}
