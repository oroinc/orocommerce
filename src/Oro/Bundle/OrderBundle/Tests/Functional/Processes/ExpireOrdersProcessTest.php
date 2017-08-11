<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Processes;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerAddresses;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserAddresses;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
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
use Oro\Bundle\WorkflowBundle\Model\ProcessHandler;

class ExpireOrdersProcessTest extends WebTestCase
{
    /** @var ProcessHandler */
    protected $processHandler;

    /** @var ProcessDefinition */
    protected $processDefinition;

    /** @var ManagerRegistry */
    protected $managerRegistry;

    /** @var ConfigManager */
    protected $configManager;

    /** @var Order */
    protected $order;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->processHandler = $this->getContainer()->get('oro_workflow.process.process_handler');
        $this->processDefinition = $this->getContainer()
            ->get('doctrine')
            ->getRepository(ProcessDefinition::class)
            ->findOneBy(['name' => 'expire_orders']);

        $this->managerRegistry = $this->getContainer()->get('doctrine');
        $this->configManager = $this->getContainer()->get('oro_config.manager');

        $this->loadFixtures([
            LoadCustomers::class,
            LoadCustomerAddresses::class,
            LoadCustomerUserData::class,
            LoadCustomerUserAddresses::class,
            LoadOrders::class,
        ]);
    }

    public function testProcessDefinition()
    {
        $this->assertNotNull($this->processDefinition);
        $this->assertNotEmpty($this->processDefinition->getPreConditionsConfiguration());
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
        $this->configManager->reset('oro_order.order_automation_enable_cancellation');
        $this->configManager->reset('oro_order.order_automation_applicable_statuses');
        $this->configManager->reset('oro_order.order_automation_target_status');
        $this->configManager->flush();

        $order = $this->prepareOrderObject();
        $internalStatus = $order->getInternalStatus();
        $this->executeProcessForOrder($order);
        $order = $this->reloadOrder($order);
        $this->assertEquals($internalStatus, $order->getInternalStatus());
    }

    public function testExecuteWithDisabledAutomation()
    {
        $this->configManager->set('oro_order.order_automation_enable_cancellation', false);
        $this->configManager->flush();

        $order = $this->prepareOrderObject((new \DateTime())->modify('-1 day'));
        $internalStatus = $order->getInternalStatus();
        $this->executeProcessForOrder($order);
        $order = $this->reloadOrder($order);
        $this->assertEquals($internalStatus, $order->getInternalStatus());
    }

    public function testExecuteDefault()
    {
        $this->configManager->reset('oro_order.order_automation_enable_cancellation');
        $this->configManager->reset('oro_order.order_automation_applicable_statuses');
        $this->configManager->reset('oro_order.order_automation_target_status');
        $this->configManager->flush();

        $order = $this->prepareOrderObject((new \DateTime())->modify('-1 day'));
        $this->executeProcessForOrder($order);
        $order = $this->reloadOrder($order);
        $this->assertEquals(
            $this->getOrderInternalStatusById(Order::INTERNAL_STATUS_CANCELLED)->getId(),
            $order->getInternalStatus()->getId()
        );
    }

    public function testExecuteWithCurrentDateForDNSL()
    {
        $this->configManager->reset('oro_order.order_automation_enable_cancellation');
        $this->configManager->reset('oro_order.order_automation_applicable_statuses');
        $this->configManager->reset('oro_order.order_automation_target_status');
        $this->configManager->flush();

        $order = $this->prepareOrderObject((new \DateTime()));
        $internalStatus = $order->getInternalStatus();
        $this->executeProcessForOrder($order);
        $order = $this->reloadOrder($order);
        $this->assertEquals(
            $internalStatus->getId(),
            $order->getInternalStatus()->getId()
        );
    }

    public function testExecuteWithOverriddenTargetStatus()
    {
        $this->configManager->reset('oro_order.order_automation_enable_cancellation');
        $this->configManager->reset('oro_order.order_automation_applicable_statuses');
        $this->configManager->set('oro_order.order_automation_target_status', 'closed');
        $this->configManager->flush();

        $order = $this->prepareOrderObject((new \DateTime())->modify('-1 day'));
        $this->executeProcessForOrder($order);
        $order = $this->reloadOrder($order);
        $this->assertEquals(
            $this->getOrderInternalStatusById('closed')->getId(),
            $order->getInternalStatus()->getId()
        );
    }

    public function testExecuteWithOverriddenApplicableStatuses()
    {
        $this->configManager->reset('oro_order.order_automation_enable_cancellation');
        $this->configManager->set('oro_order.order_automation_applicable_statuses', ['closed']);
        $this->configManager->reset('oro_order.order_automation_target_status');
        $this->configManager->flush();

        $order = $this->prepareOrderObject((new \DateTime())->modify('-1 day'));
        $this->executeProcessForOrder($order);
        $order = $this->reloadOrder($order);
        $this->assertEquals(
            $this->getOrderInternalStatusById(Order::INTERNAL_STATUS_OPEN)->getId(),
            $order->getInternalStatus()->getId()
        );

        $order = $this->prepareOrderObject((new \DateTime())->modify('-1 day'), 'closed');
        $this->executeProcessForOrder($order);
        $order = $this->reloadOrder($order);
        $this->assertEquals(
            $this->getOrderInternalStatusById(Order::INTERNAL_STATUS_CANCELLED)->getId(),
            $order->getInternalStatus()->getId()
        );
    }

    /**
     * @param Order $order
     */
    protected function executeProcessForOrder(Order $order)
    {
        $trigger = new ProcessTrigger();
        $trigger->setId($this->processDefinition->getName());
        $trigger->setDefinition($this->processDefinition);
        $trigger->setEvent(ProcessTrigger::EVENT_CREATE);

        $data = new ProcessData();
        $data->set('data', $order);

        $this->processHandler->handleTrigger($trigger, $data);
    }

    /**
     * @param \DateTime $doNotShipLater
     * @param string $internalStatus
     *
     * @return Order
     */
    protected function prepareOrderObject(
        \DateTime $doNotShipLater = null,
        $internalStatus = Order::INTERNAL_STATUS_OPEN
    ) {
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

        $em = $this->managerRegistry->getManager();
        $em->persist($order);
        $em->flush();

        return $this->reloadOrder($order);
    }

    /**
     * @param string $id
     *
     * @return object|AbstractEnumValue
     * @throws \InvalidArgumentException
     */
    protected function getOrderInternalStatusById($id = Order::INTERNAL_STATUS_OPEN)
    {
        $className = ExtendHelper::buildEnumValueClassName(Order::INTERNAL_STATUS_CODE);

        return $this->managerRegistry->getManagerForClass($className)->getRepository($className)->find($id);
    }

    /**
     * @param Order $order
     *
     * @return Order
     */
    protected function reloadOrder(Order $order)
    {
        return $this->managerRegistry->getManager()
            ->getRepository(Order::class)
            ->findOneBy(['id' => $order->getId()]);
    }

    /**
     * @return Website
     */
    protected function getDefaultWebsite()
    {
        return $this->managerRegistry->getRepository(Website::class)->findOneBy(['default' => true]);
    }
}
