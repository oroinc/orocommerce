<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async;

use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Async\CombinedPriceListCurrencyProcessor;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveCombinedPriceListCurrenciesTopic;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListCurrency;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class CombinedPriceListCurrencyProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var CombinedPriceListProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $combinedPriceListProvider;

    /** @var CombinedPriceListCurrencyProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->combinedPriceListProvider = $this->createMock(CombinedPriceListProvider::class);

        $this->processor = new CombinedPriceListCurrencyProcessor(
            $this->doctrine,
            $this->combinedPriceListProvider
        );
        $this->processor->setLogger($this->logger);
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
            ->willReturn($body);

        return $message;
    }

    private function getSession(): SessionInterface
    {
        return $this->createMock(SessionInterface::class);
    }

    public function testGetSubscribedTopics()
    {
        $this->assertSame(
            [ResolveCombinedPriceListCurrenciesTopic::getName()],
            CombinedPriceListCurrencyProcessor::getSubscribedTopics()
        );
    }

    public function testProcess()
    {
        $priceListId = 1;
        $body = ['product' => [$priceListId => []]];

        $relations = [new CombinedPriceListToPriceList()];
        $cpl = new CombinedPriceList();

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->never()))
            ->method('rollback');
        $em->expects(($this->once()))
            ->method('commit');

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(CombinedPriceListCurrency::class)
            ->willReturn($em);

        $repository = $this->createMock(CombinedPriceListRepository::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('getCombinedPriceListsByPriceLists')
            ->with([$priceListId])
            ->willReturn([$cpl]);
        $repository->expects($this->once())
            ->method('getPriceListRelations')
            ->with($cpl)
            ->willReturn($relations);

        $this->combinedPriceListProvider->expects($this->once())
            ->method('actualizeCurrencies')
            ->with($cpl, $relations);

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessException()
    {
        $priceListId = 1;
        $body = ['product' => [$priceListId => []]];

        $exception = new \Exception('some error');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->once()))
            ->method('rollback');
        $em->expects(($this->never()))
            ->method('commit');

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(CombinedPriceListCurrency::class)
            ->willReturn($em);

        $repository = $this->createMock(CombinedPriceListRepository::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('getCombinedPriceListsByPriceLists')
            ->with([$priceListId])
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Combined Price Lists currencies merging.',
                ['exception' => $exception]
            );

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessDeadlockException()
    {
        $priceListId = 1;
        $body = ['product' => [$priceListId => []]];

        $exception = $this->createMock(DeadlockException::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->once()))
            ->method('rollback');
        $em->expects(($this->never()))
            ->method('commit');

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(CombinedPriceListCurrency::class)
            ->willReturn($em);

        $repository = $this->createMock(CombinedPriceListRepository::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('getCombinedPriceListsByPriceLists')
            ->with([$priceListId])
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Combined Price Lists currencies merging.',
                ['exception' => $exception]
            );

        $this->assertEquals(
            MessageProcessorInterface::REQUEUE,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }
}
