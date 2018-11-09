<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Driver\PDOException;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\PricingBundle\Async\CombinedPriceListCurrencyProcessor;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListCurrency;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListTrigger;
use Oro\Bundle\PricingBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerFactory;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class CombinedPriceListCurrencyProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PriceListTriggerFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $triggerFactory;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    /**
     * @var CombinedPriceListProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $combinedPriceListProvider;

    /**
     * @var CombinedPriceListCurrencyProcessor
     */
    private $processor;

    protected function setUp()
    {
        $this->triggerFactory = $this->createMock(PriceListTriggerFactory::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->combinedPriceListProvider = $this->createMock(CombinedPriceListProvider::class);

        $this->processor = new CombinedPriceListCurrencyProcessor(
            $this->logger,
            $this->triggerFactory,
            $this->registry,
            $this->combinedPriceListProvider
        );
    }

    public function testProcess()
    {
        $relations = [new CombinedPriceListToPriceList()];
        $cpl = new CombinedPriceList();

        $cplRepo = $this->createMock(CombinedPriceListRepository::class);
        $cplRepo->expects($this->once())
            ->method('getCombinedPriceListsByPriceLists')
            ->with([42])
            ->willReturn([$cpl]);
        $cplRepo->expects($this->once())
            ->method('getPriceListRelations')
            ->with($cpl)
            ->willReturn($relations);

        $currencyEm = $this->createMock(EntityManagerInterface::class);
        $cplEm = $this->createMock(EntityManagerInterface::class);
        $cplEm->expects($this->once())
            ->method('getRepository')
            ->willReturn($cplRepo);

        $currencyEm->expects($this->once())
            ->method('beginTransaction');

        $currencyEm->expects(($this->never()))
            ->method('rollback');

        $currencyEm->expects(($this->once()))
            ->method('commit');

        $this->registry->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->withConsecutive(
                [CombinedPriceListCurrency::class],
                [CombinedPriceList::class]
            )
            ->willReturnOnConsecutiveCalls(
                $currencyEm,
                $cplEm
            );

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message * */
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode(['product' => [42 => []]]));

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session * */
        $session = $this->createMock(SessionInterface::class);

        $trigger = new PriceListTrigger(['42' => []]);
        $this->triggerFactory->expects($this->once())
            ->method('createFromArray')
            ->willReturn($trigger);

        $this->logger->expects($this->never())
            ->method($this->anything());

        $this->combinedPriceListProvider->expects($this->once())
            ->method('actualizeCurrencies')
            ->with($cpl, $relations);

        $this->assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $session));
    }

    /**
     * @dataProvider getProcessWithExceptionDataProvider
     *
     * @param \Exception $exception
     * @param string $result
     */
    public function testProcessWithException($exception, $result)
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $em->expects($this->once())
            ->method('beginTransaction');

        $em->expects(($this->once()))
            ->method('rollback');

        $em->expects(($this->never()))
            ->method('commit');

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(CombinedPriceListCurrency::class)
            ->willReturn($em);

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message * */
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('getBody')
            ->willReturn('');

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session * */
        $session = $this->createMock(SessionInterface::class);

        $this->triggerFactory->expects($this->once())
            ->method('createFromArray')
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error');

        $this->assertEquals($result, $this->processor->process($message, $session));
    }

    /**
     * @return array
     */
    public function getProcessWithExceptionDataProvider()
    {
        return [
            'process InvalidArgumentException' => [
                'exception' => new InvalidArgumentException(),
                'result' => MessageProcessorInterface::REJECT
            ],
            'process exception' => [
                'exception' => new \Exception(),
                'result' => MessageProcessorInterface::REJECT
            ],
            'process deadlock' => [
                'exception' => new DeadlockException('deadlock', new PDOException(new \PDOException())),
                'result' => MessageProcessorInterface::REQUEUE
            ],
        ];
    }

    public function testGetSubscribedTopics()
    {
        $this->assertSame(
            [Topics::RESOLVE_COMBINED_CURRENCIES],
            CombinedPriceListCurrencyProcessor::getSubscribedTopics()
        );
    }
}
