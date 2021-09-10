<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async;

use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Async\CombinedPriceListProcessor;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListsBuilderFacade;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CombinedPriceListProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var CombinedPriceListTriggerHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $triggerHandler;

    /** @var CombinedPriceListsBuilderFacade|\PHPUnit\Framework\MockObject\MockObject */
    private $combinedPriceListsBuilderFacade;

    /** @var CombinedPriceListProcessor */
    private $processor;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->triggerHandler = $this->createMock(CombinedPriceListTriggerHandler::class);
        $this->combinedPriceListsBuilderFacade = $this->createMock(CombinedPriceListsBuilderFacade::class);

        $this->processor = new CombinedPriceListProcessor(
            $this->doctrine,
            $this->logger,
            $this->triggerHandler,
            $this->combinedPriceListsBuilderFacade
        );
    }

    /**
     * @param mixed $body
     *
     * @return MessageInterface
     */
    private function getMessage($body): MessageInterface
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('getBody')
            ->willReturn(JSON::encode($body));

        return $message;
    }

    private function getSession(): SessionInterface
    {
        return $this->createMock(SessionInterface::class);
    }

    private function getWebsite(int $id): Website
    {
        $website = $this->createMock(Website::class);
        $website->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        return $website;
    }

    private function getCustomerGroup(int $id): CustomerGroup
    {
        $customerGroup = $this->createMock(CustomerGroup::class);
        $customerGroup->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        return $customerGroup;
    }

    private function getCustomer(int $id): Customer
    {
        $customer = $this->createMock(Customer::class);
        $customer->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        return $customer;
    }

    public function testGetSubscribedTopics()
    {
        $this->assertEquals(
            [Topics::REBUILD_COMBINED_PRICE_LISTS],
            CombinedPriceListProcessor::getSubscribedTopics()
        );
    }

    public function testProcessWithInvalidMessage()
    {
        $this->logger->expects($this->once())
            ->method('critical')
            ->with('Got invalid message.');

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage('invalid'), $this->getSession())
        );
    }

    public function testProcessRebuildForWebsitesWhenWebsiteNotFound()
    {
        $websiteId = 1;
        $body = ['website' => 1];

        $this->triggerHandler->expects($this->once())
            ->method('startCollect');
        $this->triggerHandler->expects($this->once())
            ->method('rollback');
        $this->triggerHandler->expects($this->never())
            ->method('commit');

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Website::class)
            ->willReturn($em);
        $em->expects($this->once())
            ->method('find')
            ->with(Website::class, $websiteId)
            ->willReturn(null);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Message is invalid: Website was not found.');

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessRebuildForCustomerGroupsWhenCustomerGroupNotFound()
    {
        $customerGroupId = 10;
        $body = ['website' => 1, 'customerGroup' => $customerGroupId];

        $this->triggerHandler->expects($this->once())
            ->method('startCollect');
        $this->triggerHandler->expects($this->once())
            ->method('rollback');
        $this->triggerHandler->expects($this->never())
            ->method('commit');

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(CustomerGroup::class)
            ->willReturn($em);
        $em->expects($this->once())
            ->method('find')
            ->with(CustomerGroup::class, $customerGroupId)
            ->willReturn(null);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Message is invalid: Customer Group was not found.');

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessRebuildForCustomerGroupsWhenWebsiteNotFound()
    {
        $websiteId = 1;
        $customerGroupId = 10;
        $body = ['website' => $websiteId, 'customerGroup' => $customerGroupId];

        $this->triggerHandler->expects($this->once())
            ->method('startCollect');
        $this->triggerHandler->expects($this->once())
            ->method('rollback');
        $this->triggerHandler->expects($this->never())
            ->method('commit');

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->willReturnMap([
                [CustomerGroup::class, $em],
                [Website::class, $em]
            ]);
        $em->expects($this->exactly(2))
            ->method('find')
            ->willReturnMap([
                [CustomerGroup::class, $customerGroupId, $this->getCustomerGroup($customerGroupId)],
                [Website::class, $websiteId, null]
            ]);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Message is invalid: Website was not found.');

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessRebuildForCustomersWhenCustomerNotFound()
    {
        $customerId = 100;
        $body = ['website' => 1, 'customer' => $customerId];

        $this->triggerHandler->expects($this->once())
            ->method('startCollect');
        $this->triggerHandler->expects($this->once())
            ->method('rollback');
        $this->triggerHandler->expects($this->never())
            ->method('commit');

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Customer::class)
            ->willReturn($em);
        $em->expects($this->once())
            ->method('find')
            ->with(Customer::class, $customerId)
            ->willReturn(null);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Message is invalid: Customer was not found.');

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessRebuildForCustomersWhenWebsiteNotFound()
    {
        $websiteId = 1;
        $customerId = 100;
        $body = ['website' => $websiteId, 'customer' => $customerId];

        $this->triggerHandler->expects($this->once())
            ->method('startCollect');
        $this->triggerHandler->expects($this->once())
            ->method('rollback');
        $this->triggerHandler->expects($this->never())
            ->method('commit');

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->willReturnMap([
                [Customer::class, $em],
                [Website::class, $em]
            ]);
        $em->expects($this->exactly(2))
            ->method('find')
            ->willReturnMap([
                [Customer::class, $customerId, $this->getCustomer($customerId)],
                [Website::class, $websiteId, null]
            ]);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Message is invalid: Website was not found.');

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessRebuildForCustomersWhenMessageContainsCustomerGroup()
    {
        $websiteId = 1;
        $customerId = 100;
        $website = $this->getWebsite($websiteId);
        $customer = $this->getCustomer($customerId);
        $body = ['website' => $websiteId, 'customerGroup' => 10, 'customer' => $customerId];

        $this->triggerHandler->expects($this->once())
            ->method('startCollect');
        $this->triggerHandler->expects($this->once())
            ->method('commit');
        $this->triggerHandler->expects($this->never())
            ->method('rollback');

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->willReturnMap([
                [Customer::class, $em],
                [Website::class, $em]
            ]);
        $em->expects($this->exactly(2))
            ->method('find')
            ->willReturnMap([
                [Customer::class, $customerId, $customer],
                [Website::class, $websiteId, $website]
            ]);

        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('rebuildForCustomers')
            ->with([$customer], $website, false);
        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('dispatchEvents');

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessWhenUnexpectedExceptionOccurred()
    {
        $websiteId = 1;
        $body = ['website' => $websiteId];

        $exception = new \Exception('some error');

        $this->triggerHandler->expects($this->once())
            ->method('startCollect');
        $this->triggerHandler->expects($this->once())
            ->method('rollback');
        $this->triggerHandler->expects($this->never())
            ->method('commit');

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Website::class)
            ->willReturn($em);
        $em->expects($this->once())
            ->method('find')
            ->with(Website::class, $websiteId)
            ->willReturn($this->getWebsite($websiteId));

        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('rebuildForWebsites');
        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('dispatchEvents')
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Combined Price Lists build.',
                ['exception' => $exception]
            );

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessWhenDeadlockExceptionOccurred()
    {
        $websiteId = 1;
        $body = ['website' => $websiteId];

        $exception = $this->createMock(DeadlockException::class);

        $this->triggerHandler->expects($this->once())
            ->method('startCollect');
        $this->triggerHandler->expects($this->once())
            ->method('rollback');
        $this->triggerHandler->expects($this->never())
            ->method('commit');

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Website::class)
            ->willReturn($em);
        $em->expects($this->once())
            ->method('find')
            ->with(Website::class, $websiteId)
            ->willReturn($this->getWebsite($websiteId));

        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('rebuildForWebsites');
        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('dispatchEvents')
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Combined Price Lists build.',
                ['exception' => $exception]
            );

        $this->assertEquals(
            MessageProcessorInterface::REQUEUE,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessRebuildForWebsites()
    {
        $websiteId = 1;
        $website = $this->getWebsite($websiteId);
        $body = ['website' => $websiteId];

        $this->triggerHandler->expects($this->once())
            ->method('startCollect');
        $this->triggerHandler->expects($this->once())
            ->method('commit');
        $this->triggerHandler->expects($this->never())
            ->method('rollback');

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Website::class)
            ->willReturn($em);
        $em->expects($this->once())
            ->method('find')
            ->with(Website::class, $websiteId)
            ->willReturn($website);

        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('rebuildForWebsites')
            ->with([$website], false);
        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('dispatchEvents');

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessRebuildForCustomerGroups()
    {
        $websiteId = 1;
        $customerGroupId = 10;
        $website = $this->getWebsite($websiteId);
        $customerGroup = $this->getCustomerGroup($customerGroupId);
        $body = ['website' => $websiteId, 'customerGroup' => $customerGroupId];

        $this->triggerHandler->expects($this->once())
            ->method('startCollect');
        $this->triggerHandler->expects($this->once())
            ->method('commit');
        $this->triggerHandler->expects($this->never())
            ->method('rollback');

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->willReturnMap([
                [CustomerGroup::class, $em],
                [Website::class, $em]
            ]);
        $em->expects($this->exactly(2))
            ->method('find')
            ->willReturnMap([
                [CustomerGroup::class, $customerGroupId, $customerGroup],
                [Website::class, $websiteId, $website]
            ]);

        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('rebuildForCustomerGroups')
            ->with([$customerGroup], $website, false);
        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('dispatchEvents');

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessRebuildForCustomers()
    {
        $websiteId = 1;
        $customerId = 100;
        $website = $this->getWebsite($websiteId);
        $customer = $this->getCustomer($customerId);
        $body = ['website' => $websiteId, 'customer' => $customerId];

        $this->triggerHandler->expects($this->once())
            ->method('startCollect');
        $this->triggerHandler->expects($this->once())
            ->method('commit');
        $this->triggerHandler->expects($this->never())
            ->method('rollback');

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->willReturnMap([
                [Customer::class, $em],
                [Website::class, $em]
            ]);
        $em->expects($this->exactly(2))
            ->method('find')
            ->willReturnMap([
                [Customer::class, $customerId, $customer],
                [Website::class, $websiteId, $website]
            ]);

        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('rebuildForCustomers')
            ->with([$customer], $website, false);
        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('dispatchEvents');

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessRebuildAll()
    {
        $body = [];

        $this->triggerHandler->expects($this->once())
            ->method('startCollect');
        $this->triggerHandler->expects($this->once())
            ->method('commit');
        $this->triggerHandler->expects($this->never())
            ->method('rollback');

        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('rebuildAll')
            ->with(false);
        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('dispatchEvents');

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessRebuildAllWithForce()
    {
        $body = ['force' => true];

        $this->triggerHandler->expects($this->once())
            ->method('startCollect');
        $this->triggerHandler->expects($this->once())
            ->method('commit');
        $this->triggerHandler->expects($this->never())
            ->method('rollback');

        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('rebuildAll')
            ->with(true);
        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('dispatchEvents');

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }
}
