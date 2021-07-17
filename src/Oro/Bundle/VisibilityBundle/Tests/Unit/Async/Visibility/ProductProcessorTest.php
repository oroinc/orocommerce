<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Async\Visibility;

use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\VisibilityBundle\Async\Topics;
use Oro\Bundle\VisibilityBundle\Async\Visibility\ProductProcessor;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\ProductCaseCacheBuilderInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class ProductProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var ProductCaseCacheBuilderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cacheBuilder;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var ProductProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->cacheBuilder = $this->createMock(ProductCaseCacheBuilderInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new ProductProcessor(
            $this->doctrine,
            $this->logger,
            $this->cacheBuilder
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

    public function testGetSubscribedTopics()
    {
        $this->assertEquals(
            [Topics::CHANGE_PRODUCT_CATEGORY],
            ProductProcessor::getSubscribedTopics()
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

    public function testProcessWithEmptyMessage()
    {
        $this->logger->expects($this->once())
            ->method('critical')
            ->with('Got invalid message.');

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage([]), $this->getSession())
        );
    }

    public function testProcess()
    {
        $body = ['id' => 42];

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->never()))
            ->method('rollback');
        $em->expects(($this->once()))
            ->method('commit');

        $this->doctrine->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->willReturnMap([
                [ProductVisibilityResolved::class, $em],
                [Product::class, $em]
            ]);

        $product = new Product();

        $repository = $this->createMock(ProductRepository::class);
        $repository
            ->expects($this->once())
            ->method('findBy')
            ->with(['id' => [$body['id']]])
            ->willReturn([$product]);

        $em->expects($this->once())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($repository);

        $this->cacheBuilder->expects($this->once())
            ->method('productCategoryChanged')
            ->with($product);

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessDeadlock()
    {
        $body = ['id' => 42];

        $exception = $this->createMock(DeadlockException::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->once()))
            ->method('rollback');
        $em->expects(($this->never()))
            ->method('commit');

        $this->doctrine->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->willReturnMap([
                [ProductVisibilityResolved::class, $em],
                [Product::class, $em]
            ]);

        $product = new Product();

        $repository = $this->createMock(ProductRepository::class);
        $repository
            ->expects($this->once())
            ->method('findBy')
            ->with(['id' => [$body['id']]])
            ->willReturn([$product]);

        $em->expects($this->once())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($repository);

        $this->cacheBuilder->expects($this->once())
            ->method('productCategoryChanged')
            ->with($product)
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Product Visibility resolve by Product.',
                ['exception' => $exception]
            );

        $this->assertEquals(
            MessageProcessorInterface::REQUEUE,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessException()
    {
        $body = ['id' => 42];

        $exception = new \Exception('Some error');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->once()))
            ->method('rollback');
        $em->expects(($this->never()))
            ->method('commit');

        $this->doctrine->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->willReturnMap([
                [ProductVisibilityResolved::class, $em],
                [Product::class, $em]
            ]);

        $product = new Product();

        $repository = $this->createMock(ProductRepository::class);
        $repository
            ->expects($this->once())
            ->method('findBy')
            ->with(['id' => [$body['id']]])
            ->willReturn([$product]);

        $em->expects($this->once())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($repository);

        $this->cacheBuilder->expects($this->once())
            ->method('productCategoryChanged')
            ->with($product)
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Product Visibility resolve by Product.',
                ['exception' => $exception]
            );

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessWhenProductNotFound()
    {
        $body = ['id' => 42];

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->once()))
            ->method('rollback');
        $em->expects(($this->never()))
            ->method('commit');

        $this->doctrine->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->willReturnMap([
                [ProductVisibilityResolved::class, $em],
                [Product::class, $em]
            ]);

        $repository = $this->createMock(ProductRepository::class);
        $repository
            ->expects($this->once())
            ->method('findBy')
            ->with(['id' => [$body['id']]])
            ->willReturn([]);

        $em->expects($this->once())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($repository);

        $this->cacheBuilder->expects($this->never())
            ->method('productCategoryChanged');

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('The following products have not been not found when trying to resolve visibility');

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Product Visibility resolve by Product.',
                [
                    'exception' => new EntityNotFoundException(
                        'Products have not been found when trying to resolve visibility: 42'
                    ),
                ]
            );

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }
}
