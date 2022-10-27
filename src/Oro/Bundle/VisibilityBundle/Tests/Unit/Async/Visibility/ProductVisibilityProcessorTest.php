<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Async\Visibility;

use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Bundle\VisibilityBundle\Async\Topic\ResolveProductVisibilityTopic;
use Oro\Bundle\VisibilityBundle\Async\Visibility\ProductVisibilityProcessor;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\CacheBuilderInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductVisibilityProcessorTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $doctrine;

    private CacheBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $cacheBuilder;

    private ProductVisibilityProcessor $processor;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->cacheBuilder = $this->createMock(CacheBuilderInterface::class);

        $this->processor = new ProductVisibilityProcessor($this->doctrine, $this->cacheBuilder);
        $this->setUpLoggerMock($this->processor);
    }

    private function getMessage(array $body): MessageInterface
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('getBody')
            ->willReturn($body);

        return $message;
    }

    private function getSession(): SessionInterface
    {
        return $this->createMock(SessionInterface::class);
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals(
            [ResolveProductVisibilityTopic::getName()],
            ProductVisibilityProcessor::getSubscribedTopics()
        );
    }

    public function testProcess(): void
    {
        $body = ['entity_class_name' => ProductVisibility::class, 'id' => 42];

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects((self::never()))
            ->method('rollback');
        $em->expects((self::once()))
            ->method('commit');

        $this->doctrine->expects(self::exactly(2))
            ->method('getManagerForClass')
            ->willReturnMap([
                [ProductVisibilityResolved::class, $em],
                [ProductVisibility::class, $em],
            ]);

        $visibility = new ProductVisibility();
        $em->expects(self::once())
            ->method('find')
            ->with(ProductVisibility::class, $body['id'])
            ->willReturn($visibility);
        $this->cacheBuilder->expects(self::once())
            ->method('resolveVisibilitySettings')
            ->with(self::identicalTo($visibility));

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessEntityNotFound(): void
    {
        $body = ['entity_class_name' => ProductVisibility::class, 'id' => 42];

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects((self::once()))
            ->method('rollback');
        $em->expects((self::never()))
            ->method('commit');

        $this->doctrine->expects(self::exactly(2))
            ->method('getManagerForClass')
            ->willReturnMap([
                [ProductVisibilityResolved::class, $em],
                [ProductVisibility::class, $em],
            ]);

        $em->expects(self::once())
            ->method('find')
            ->with(ProductVisibility::class, $body['id'])
            ->willReturn(null);
        $this->cacheBuilder->expects(self::never())
            ->method('resolveVisibilitySettings');

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Product Visibility resolve.',
                ['exception' => new EntityNotFoundException('Entity object was not found.')]
            );

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessWithoutEntityId(): void
    {
        $body = [
            'entity_class_name' => ProductVisibility::class,
            'target_class_name' => Product::class,
            'target_id' => 12,
            'scope_id' => 1,
        ];

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects((self::never()))
            ->method('rollback');
        $em->expects((self::once()))
            ->method('commit');

        $this->doctrine->expects(self::exactly(3))
            ->method('getManagerForClass')
            ->willReturnMap([
                [ProductVisibilityResolved::class, $em],
                [$body['target_class_name'], $em],
                [Scope::class, $em],
            ]);

        $product = new Product();
        $scope = new Scope();
        $em->expects(self::exactly(2))
            ->method('find')
            ->willReturnMap([
                [$body['target_class_name'], $body['target_id'], $product],
                [Scope::class, $body['scope_id'], $scope],
            ]);
        $this->cacheBuilder->expects(self::once())
            ->method('resolveVisibilitySettings')
            ->with(self::isInstanceOf(ProductVisibility::class))
            ->willReturnCallback(function (ProductVisibility $visibility) use ($product, $scope) {
                $this->assertSame($product, $visibility->getTargetEntity());
                $this->assertSame($scope, $visibility->getScope());
                $this->assertSame(ProductVisibility::CATEGORY, $visibility->getVisibility());
            });

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessWithoutEntityIdAndScopeNotFound(): void
    {
        $body = [
            'entity_class_name' => ProductVisibility::class,
            'target_class_name' => Product::class,
            'target_id' => 12,
            'scope_id' => 1,
        ];

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects((self::once()))
            ->method('rollback');
        $em->expects((self::never()))
            ->method('commit');

        $this->doctrine->expects(self::exactly(3))
            ->method('getManagerForClass')
            ->willReturnMap([
                [ProductVisibilityResolved::class, $em],
                [$body['target_class_name'], $em],
                [Scope::class, $em],
            ]);

        $product = new Product();
        $em->expects(self::exactly(2))
            ->method('find')
            ->willReturnMap([
                [$body['target_class_name'], $body['target_id'], $product],
                [Scope::class, $body['scope_id'], null],
            ]);
        $this->cacheBuilder->expects(self::never())
            ->method('resolveVisibilitySettings');

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Product Visibility resolve.',
                ['exception' => new EntityNotFoundException('Scope object object was not found.')]
            );

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessWithoutEntityIdAndTargetEntityNotFound(): void
    {
        $body = [
            'entity_class_name' => ProductVisibility::class,
            'target_class_name' => Product::class,
            'target_id' => 12,
            'scope_id' => 1,
        ];

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects((self::once()))
            ->method('rollback');
        $em->expects((self::never()))
            ->method('commit');

        $this->doctrine->expects(self::exactly(2))
            ->method('getManagerForClass')
            ->willReturnMap([
                [ProductVisibilityResolved::class, $em],
                [$body['target_class_name'], $em],
            ]);

        $em->expects(self::once())
            ->method('find')
            ->with($body['target_class_name'], $body['target_id'])
            ->willReturn(null);
        $this->cacheBuilder->expects(self::never())
            ->method('resolveVisibilitySettings');

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Product Visibility resolve.',
                ['exception' => new EntityNotFoundException('Target object was not found.')]
            );

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessDeadlock(): void
    {
        $body = ['entity_class_name' => ProductVisibility::class, 'id' => 42];

        $exception = $this->createMock(DeadlockException::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects((self::once()))
            ->method('rollback');
        $em->expects((self::never()))
            ->method('commit');

        $this->doctrine->expects(self::exactly(2))
            ->method('getManagerForClass')
            ->willReturnMap([
                [ProductVisibilityResolved::class, $em],
                [ProductVisibility::class, $em],
            ]);

        $visibility = new ProductVisibility();
        $em->expects(self::once())
            ->method('find')
            ->with(ProductVisibility::class, $body['id'])
            ->willReturn($visibility);
        $this->cacheBuilder->expects(self::once())
            ->method('resolveVisibilitySettings')
            ->with(self::identicalTo($visibility))
            ->willThrowException($exception);

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Product Visibility resolve.',
                ['exception' => $exception]
            );

        self::assertEquals(
            MessageProcessorInterface::REQUEUE,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessException(): void
    {
        $body = ['entity_class_name' => ProductVisibility::class, 'id' => 42];

        $exception = new \Exception('some error');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects((self::once()))
            ->method('rollback');
        $em->expects((self::never()))
            ->method('commit');

        $this->doctrine->expects(self::exactly(2))
            ->method('getManagerForClass')
            ->willReturnMap([
                [ProductVisibilityResolved::class, $em],
                [ProductVisibility::class, $em],
            ]);

        $visibility = new ProductVisibility();
        $em->expects(self::once())
            ->method('find')
            ->with(ProductVisibility::class, $body['id'])
            ->willReturn($visibility);
        $this->cacheBuilder->expects(self::once())
            ->method('resolveVisibilitySettings')
            ->with(self::identicalTo($visibility))
            ->willThrowException($exception);

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Product Visibility resolve.',
                ['exception' => $exception]
            );

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }
}
