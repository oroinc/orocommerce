<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Async\Visibility;

use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Bundle\VisibilityBundle\Async\Topic\VisibilityOnChangeProductCategoryTopic;
use Oro\Bundle\VisibilityBundle\Async\Visibility\ProductProcessor;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\ProductCaseCacheBuilderInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class ProductProcessorTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private ProductCaseCacheBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $cacheBuilder;

    private ProductProcessor $processor;

    private EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $entityManager;

    private ProductRepository|\PHPUnit\Framework\MockObject\MockObject $repository;

    protected function setUp(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->cacheBuilder = $this->createMock(ProductCaseCacheBuilderInterface::class);

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $managerRegistry->expects(self::any())
            ->method('getManagerForClass')
            ->with(ProductVisibilityResolved::class)
            ->willReturn($this->entityManager);

        $this->repository = $this->createMock(ProductRepository::class);
        $managerRegistry
            ->expects(self::any())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($this->repository);

        $this->processor = new ProductProcessor($managerRegistry, $this->cacheBuilder);
        $this->setUpLoggerMock($this->processor);
    }

    private function getMessage(array $body): MessageInterface
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('getBody')
            ->willReturn($body + ['scheduleReindex' => false]);

        return $message;
    }

    private function getSession(): SessionInterface
    {
        return $this->createMock(SessionInterface::class);
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals(
            [VisibilityOnChangeProductCategoryTopic::getName()],
            ProductProcessor::getSubscribedTopics()
        );
    }

    public function testProcess(): void
    {
        $body = ['id' => 42];

        $this->entityManager->expects(self::once())
            ->method('beginTransaction');
        $this->entityManager->expects((self::never()))
            ->method('rollback');
        $this->entityManager->expects((self::once()))
            ->method('commit');

        $product = new Product();

        $this->repository
            ->expects(self::once())
            ->method('findBy')
            ->with(['id' => [$body['id']]])
            ->willReturn([$product]);

        $this->cacheBuilder->expects(self::once())
            ->method('productCategoryChanged')
            ->with($product);

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessDeadlock(): void
    {
        $body = ['id' => 42];

        $exception = $this->createMock(DeadlockException::class);

        $this->entityManager->expects(self::once())
            ->method('beginTransaction');
        $this->entityManager->expects((self::once()))
            ->method('rollback');
        $this->entityManager->expects((self::never()))
            ->method('commit');

        $product = new Product();

        $this->repository
            ->expects(self::once())
            ->method('findBy')
            ->with(['id' => [$body['id']]])
            ->willReturn([$product]);

        $this->cacheBuilder->expects(self::once())
            ->method('productCategoryChanged')
            ->with($product)
            ->willThrowException($exception);

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Product Visibility resolve by Product.',
                ['exception' => $exception]
            );

        self::assertEquals(
            MessageProcessorInterface::REQUEUE,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessException(): void
    {
        $body = ['id' => 42];

        $exception = new \Exception('Some error');

        $this->entityManager->expects(self::once())
            ->method('beginTransaction');
        $this->entityManager->expects((self::once()))
            ->method('rollback');
        $this->entityManager->expects((self::never()))
            ->method('commit');

        $product = new Product();

        $this->repository
            ->expects(self::once())
            ->method('findBy')
            ->with(['id' => [$body['id']]])
            ->willReturn([$product]);

        $this->cacheBuilder->expects(self::once())
            ->method('productCategoryChanged')
            ->with($product)
            ->willThrowException($exception);

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Product Visibility resolve by Product.',
                ['exception' => $exception]
            );

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessWhenProductNotFound(): void
    {
        $body = ['id' => 42];

        $this->entityManager->expects(self::once())
            ->method('beginTransaction');
        $this->entityManager->expects((self::once()))
            ->method('rollback');
        $this->entityManager->expects((self::never()))
            ->method('commit');

        $this->repository
            ->expects(self::once())
            ->method('findBy')
            ->with(['id' => [$body['id']]])
            ->willReturn([]);

        $this->cacheBuilder->expects(self::never())
            ->method('productCategoryChanged');

        $this->loggerMock->expects(self::once())
            ->method('warning')
            ->with('The following products have not been not found when trying to resolve visibility');

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Product Visibility resolve by Product.',
                [
                    'exception' => new EntityNotFoundException(
                        'Products have not been found when trying to resolve visibility: 42'
                    ),
                ]
            );

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }
}
