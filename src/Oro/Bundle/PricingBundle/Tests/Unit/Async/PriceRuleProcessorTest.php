<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\PricingBundle\Async\PriceListProcessor;
use Oro\Bundle\PricingBundle\Async\PriceRuleProcessor;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Builder\PriceListProductAssignmentBuilder;
use Oro\Bundle\PricingBundle\Builder\ProductPriceBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListTrigger;
use Oro\Bundle\PricingBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerFactory;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class PriceRuleProcessorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var PriceListTriggerFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $triggerFactory;

    /**
     * @var PriceListProductAssignmentBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $assignmentBuilder;

    /**
     * @var ProductPriceBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceBuilder;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    /**
     * @var PriceListProcessor
     */
    protected $priceRuleProcessor;

    /**
     * @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    protected function setUp()
    {
        $this->triggerFactory = $this->getMockBuilder(PriceListTriggerFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->assignmentBuilder = $this->getMockBuilder(PriceListProductAssignmentBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->priceBuilder = $this->getMockBuilder(ProductPriceBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMock(LoggerInterface::class);
        $this->registry = $this->getMock(RegistryInterface::class);

        $this->priceRuleProcessor = new PriceRuleProcessor(
            $this->triggerFactory,
            $this->assignmentBuilder,
            $this->priceBuilder,
            $this->logger,
            $this->registry
        );
    }

    public function testProcessException()
    {
        $data = ['test' => 1];
        $body = json_encode($data);

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

    public function testProcess()
    {
        $data = ['test' => 1];
        $body = json_encode($data);

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

        $this->assignmentBuilder->expects($this->once())
            ->method('buildByPriceList')
            ->with($priceList);

        $this->priceBuilder->expects($this->once())
            ->method('buildByPriceList')
            ->with($priceList, $product);

        $manager = $this->getMock(ObjectManager::class);
        $manager->expects($this->once())->method('refresh');
        $repository = $this->getMockBuilder(PriceListRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())->method('updatePriceListsActuality');
        $manager->method('getRepository')->willReturn($repository);
        $this->registry->method('getManagerForClass')->willReturn($manager);

        $this->assertEquals(MessageProcessorInterface::ACK, $this->priceRuleProcessor->process($message, $session));
    }

    public function testGetSubscribedTopics()
    {
        $this->assertEquals([Topics::CALCULATE_RULE], $this->priceRuleProcessor->getSubscribedTopics());
    }
}
