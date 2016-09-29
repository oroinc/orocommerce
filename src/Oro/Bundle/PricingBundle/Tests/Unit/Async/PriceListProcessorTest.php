<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\PricingBundle\Async\PriceListProcessor;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CombinedPriceListsUpdateEvent;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListTrigger;
use Oro\Bundle\PricingBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerFactory;
use Oro\Bundle\PricingBundle\Resolver\CombinedProductPriceResolver;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class PriceListProcessorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var PriceListTriggerFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $triggerFactory;

    /**
     * @var CombinedProductPriceResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceResolver;

    /**
     * @var EventDispatcher|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    /**
     * @var PriceListProcessor
     */
    protected $priceRuleProcessor;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var CombinedPriceListRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    protected function setUp()
    {
        $this->triggerFactory = $this->getMockBuilder(PriceListTriggerFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->priceResolver = $this->getMockBuilder(CombinedProductPriceResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventDispatcher = $this->getMockBuilder(EventDispatcher::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMock(LoggerInterface::class);

        $this->repository = $this->getMockBuilder(CombinedPriceListRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMock(ManagerRegistry::class);

        $this->priceRuleProcessor = new PriceListProcessor(
            $this->triggerFactory,
            $this->registry,
            $this->priceResolver,
            $this->eventDispatcher,
            $this->logger
        );
    }

    public function testProcessInvalidArgumentException()
    {
        $data = ['test' => 1];
        $body = json_encode($data);

        $em = $this->getMock(EntityManagerInterface::class);

        $em->expects($this->once())
            ->method('beginTransaction');

        $em->expects(($this->once()))
            ->method('rollback');

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(CombinedPriceList::class)
            ->willReturn($em);

        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->getMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($body);

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->getMock(SessionInterface::class);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                sprintf(
                    'Message is invalid: %s. Original message: "%s"',
                    'Test message',
                    $body
                )
            );

        $this->triggerFactory->expects($this->once())
            ->method('createFromArray')
            ->with($data)
            ->willThrowException(new InvalidArgumentException('Test message'));

        $this->assertEquals(MessageProcessorInterface::REJECT, $this->priceRuleProcessor->process($message, $session));
    }

    public function testProcessException()
    {
        $exception = new \Exception('Some error');

        $em = $this->getMock(EntityManagerInterface::class);

        $em->expects($this->once())
            ->method('beginTransaction');

        $em->expects(($this->once()))
            ->method('rollback');

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(CombinedPriceList::class)
            ->willReturn($em);

        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->getMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willThrowException($exception);

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->getMock(SessionInterface::class);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Unexpected exception occurred during Combined Price Lists build', ['exception' => $exception]);

        $this->triggerFactory->expects($this->never())
            ->method('createFromArray');

        $this->assertEquals(MessageProcessorInterface::REQUEUE, $this->priceRuleProcessor->process($message, $session));
    }

    public function testProcess()
    {
        $data = ['test' => 1];
        $body = json_encode($data);

        $em = $this->getMock(EntityManagerInterface::class);

        $em->expects($this->once())
            ->method('beginTransaction');

        $em->expects(($this->once()))
            ->method('commit');

        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->repository);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(CombinedPriceList::class)
            ->willReturn($em);

        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 2]);
        $trigger = new PriceListTrigger($priceList, $product);

        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->getMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($body);

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->getMock(SessionInterface::class);

        $this->triggerFactory->expects($this->once())
            ->method('createFromArray')
            ->with($data)
            ->willReturn($trigger);

        $cplId = 1;
        $cpl = $this->getMock(CombinedPriceList::class);
        $cpl->expects($this->once())
            ->method('getId')
            ->willReturn($cplId);

        $this->repository->method('getCombinedPriceListsByPriceList')
            ->with($priceList, true)
            ->willReturn([$cpl]);

        $this->priceResolver->expects($this->once())
            ->method('combinePrices')
            ->with($cpl, $product);

        $event = new CombinedPriceListsUpdateEvent([$cplId]);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(CombinedPriceListsUpdateEvent::NAME, $event);

        $this->assertEquals(MessageProcessorInterface::ACK, $this->priceRuleProcessor->process($message, $session));
    }

    public function testGetSubscribedTopics()
    {
        $this->assertEquals([Topics::RESOLVE_COMBINED_PRICES], $this->priceRuleProcessor->getSubscribedTopics());
    }
}
