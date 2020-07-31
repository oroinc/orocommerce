<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Async\Visibility;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Search\Reindex\ProductReindexManager;
use Oro\Bundle\VisibilityBundle\Async\Visibility\ProductProcessor;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Model\ProductMessageFactory;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\CacheBuilderInterface;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\ProductCaseCacheBuilderInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class ProductProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var ProductMessageFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $messageFactory;

    /**
     * @var ProductCaseCacheBuilderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $cacheBuilder;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $logger;

    /**
     * @var ProductProcessor
     */
    protected $visibilityProcessor;

    /**
     * @var ProductReindexManager|null
     */
    private $productReindexManager;

    protected function setUp()
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->messageFactory = $this->getMockBuilder(ProductMessageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cacheBuilder = $this->createMock(ProductCaseCacheBuilderInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->productReindexManager = $this->createMock(ProductReindexManager::class);
    }

    /**
     * @param CacheBuilderInterface|null $cacheBuilder
     * @param ProductReindexManager|null $productReindexManager
     *
     * @return ProductProcessor
     */
    private function getProductProcessor(
        ?CacheBuilderInterface $cacheBuilder = null,
        ?ProductReindexManager $productReindexManager = null
    ): ProductProcessor {
        $processor = new ProductProcessor(
            $this->registry,
            $this->messageFactory,
            $this->logger,
            $cacheBuilder ?: $this->cacheBuilder
        );
        $processor->setResolvedVisibilityClassName(ProductVisibilityResolved::class);

        $processor->setProductReindexManager($productReindexManager);

        return $processor;
    }

    public function testProcess(): void
    {
        $data = ['id' => 42];
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->never()))
            ->method('rollback');
        $em->expects(($this->once()))
            ->method('commit');
        $this->registry->expects($this->exactly(2))
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
            ->with(['id' => [$data['id']]])
            ->willReturn([$product]);

        $em->expects($this->once())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($repository);

        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(json_encode($data));
        $this->cacheBuilder->expects($this->once())
            ->method('productCategoryChanged')
            ->with($product);

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->getProductProcessor()->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function testProcessWhenMultipleIds(): void
    {
        $data = ['id' => [42, 43]];
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->never()))
            ->method('rollback');
        $em->expects(($this->once()))
            ->method('commit');
        $this->registry->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->willReturnMap([
                [ProductVisibilityResolved::class, $em],
                [Product::class, $em]
            ]);

        $product1 = new Product();
        $product2 = new Product();

        $repository = $this->createMock(ProductRepository::class);
        $repository
            ->expects($this->once())
            ->method('findBy')
            ->with(['id' => $data['id']])
            ->willReturn([$product1, $product2]);

        $em->expects($this->once())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($repository);

        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(json_encode($data));

        $this->cacheBuilder->expects($this->exactly(2))
            ->method('productCategoryChanged')
            ->with($product2);

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->getProductProcessor()->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function testProcessWhenMultipleIdsWithDisabledReindex(): void
    {
        $data = ['id' => [42, 43]];
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->never()))
            ->method('rollback');
        $em->expects(($this->once()))
            ->method('commit');
        $this->registry->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->willReturnMap([
                [ProductVisibilityResolved::class, $em],
                [Product::class, $em]
            ]);

        $product1 = new Product();
        $product2 = new Product();

        $repository = $this->createMock(ProductRepository::class);
        $repository
            ->expects($this->once())
            ->method('findBy')
            ->with(['id' => $data['id']])
            ->willReturn([$product1, $product2]);

        $em->expects($this->once())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($repository);

        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(json_encode($data));

        $cacheBuilder = $this->getMockBuilder(ProductCaseCacheBuilderInterface::class)
            ->setMethods(
                [
                    'productCategoryChanged',
                    'productsCategoryChangedWithDisabledReindex',
                    'toggleReindex',
                    'resolveVisibilitySettings',
                    'isVisibilitySettingsSupported',
                    'buildCache',
                ]
            )
            ->getMock();

        $cacheBuilder->expects($this->once())
            ->method('productsCategoryChangedWithDisabledReindex')
            ->with([$product1, $product2]);

        $this->productReindexManager
            ->expects($this->once())
            ->method('reindexProducts')
            ->with($data['id']);

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->getProductProcessor($cacheBuilder, $this->productReindexManager)
                ->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function testProcessDeadlock(): void
    {
        $data = ['id' => 42];

        $exception = $this->createMock(DeadlockException::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->once()))
            ->method('commit')
            ->willThrowException($exception);
        $em->expects(($this->once()))
            ->method('rollback');
        $this->registry->expects($this->exactly(2))
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
            ->with(['id' => [$data['id']]])
            ->willReturn([$product]);

        $em->expects($this->once())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($repository);

        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(json_encode($data));
        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Product Visibility resolve by Product',
                ['exception' => $exception]
            );

        $this->assertEquals(
            MessageProcessorInterface::REQUEUE,
            $this->getProductProcessor()->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function testProcessInvalidMessage(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(json_encode([]));

        $this->logger->expects($this->once())
            ->method('critical')
            ->with('Got invalid message.');

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->getProductProcessor()->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function testProcessReject(): void
    {
        $data = ['id' => 42];
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->once()))
            ->method('rollback');
        $this->registry->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->willReturnMap([
                [ProductVisibilityResolved::class, $em],
                [Product::class, $em]
            ]);

        $repository = $this->createMock(ProductRepository::class);
        $repository
            ->expects($this->once())
            ->method('findBy')
            ->with(['id' => [$data['id']]])
            ->willReturn([]);

        $em->expects($this->once())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($repository);

        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(json_encode($data));

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('The following products have not been not found when trying to resolve visibility');

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Product Visibility resolve by Product',
                [
                    'exception' => new EntityNotFoundException(
                        'Products have not been found when trying to resolve visibility: 42'
                    ),
                ]
            );

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->getProductProcessor()->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function testSetResolvedVisibilityClassName(): void
    {
        $this->assertAttributeEquals(
            ProductVisibilityResolved::class,
            'resolvedVisibilityClassName',
            $this->getProductProcessor()
        );
    }
}
