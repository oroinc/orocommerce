<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Async\Visibility;

use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Async\Topics;
use Oro\Bundle\VisibilityBundle\Async\Visibility\CategoryVisibilityProcessor;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\CacheBuilderInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CategoryVisibilityProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var CacheBuilderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cacheBuilder;

    /** @var CategoryVisibilityProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->cacheBuilder = $this->createMock(CacheBuilderInterface::class);

        $this->processor = new CategoryVisibilityProcessor(
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
            [Topics::CHANGE_CATEGORY_VISIBILITY],
            CategoryVisibilityProcessor::getSubscribedTopics()
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

    public function testProcessWithInvalidMessageDueToEntityClassDoesNotExist()
    {
        $body = ['entity_class_name' => 'Test\UnknownClass', 'id' => 42];

        $this->logger->expects($this->once())
            ->method('critical')
            ->with('Got invalid message.');

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcess()
    {
        $body = ['entity_class_name' => CategoryVisibility::class, 'id' => 42];

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
                [CategoryVisibilityResolved::class, $em],
                [CategoryVisibility::class, $em]
            ]);

        $visibility = new CategoryVisibility();
        $em->expects($this->once())
            ->method('find')
            ->with(CategoryVisibility::class, $body['id'])
            ->willReturn($visibility);
        $this->cacheBuilder->expects($this->once())
            ->method('resolveVisibilitySettings')
            ->with($this->identicalTo($visibility));

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessEntityNotFound()
    {
        $body = ['entity_class_name' => CategoryVisibility::class, 'id' => 42];

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
                [CategoryVisibilityResolved::class, $em],
                [CategoryVisibility::class, $em]
            ]);

        $em->expects($this->once())
            ->method('find')
            ->with(CategoryVisibility::class, $body['id'])
            ->willReturn(null);
        $this->cacheBuilder->expects($this->never())
            ->method('resolveVisibilitySettings');

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Product Visibility resolve.',
                ['exception' => new EntityNotFoundException('Entity object was not found.')]
            );

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessWithoutEntityId()
    {
        $body = [
            'entity_class_name' => CategoryVisibility::class,
            'target_class_name' => Category::class,
            'target_id'         => 12,
            'scope_id'          => 1
        ];

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->never()))
            ->method('rollback');
        $em->expects(($this->once()))
            ->method('commit');

        $this->doctrine->expects($this->exactly(3))
            ->method('getManagerForClass')
            ->willReturnMap([
                [CategoryVisibilityResolved::class, $em],
                [$body['target_class_name'], $em],
                [Scope::class, $em]
            ]);

        $category = new Category();
        $scope = new Scope();
        $em->expects($this->exactly(2))
            ->method('find')
            ->willReturnMap([
                [$body['target_class_name'], $body['target_id'], $category],
                [Scope::class, $body['scope_id'], $scope]
            ]);
        $this->cacheBuilder->expects($this->once())
            ->method('resolveVisibilitySettings')
            ->with($this->isInstanceOf(CategoryVisibility::class))
            ->willReturnCallback(function (CategoryVisibility $visibility) use ($category, $scope) {
                $this->assertSame($category, $visibility->getTargetEntity());
                $this->assertSame($scope, $visibility->getScope());
                $this->assertSame(CategoryVisibility::CONFIG, $visibility->getVisibility());
            });

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessWithoutEntityIdAndScopeNotFound()
    {
        $body = [
            'entity_class_name' => CategoryVisibility::class,
            'target_class_name' => Category::class,
            'target_id'         => 12,
            'scope_id'          => 1
        ];

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->once()))
            ->method('rollback');
        $em->expects(($this->never()))
            ->method('commit');

        $this->doctrine->expects($this->exactly(3))
            ->method('getManagerForClass')
            ->willReturnMap([
                [CategoryVisibilityResolved::class, $em],
                [$body['target_class_name'], $em],
                [Scope::class, $em]
            ]);

        $category = new Category();
        $em->expects($this->exactly(2))
            ->method('find')
            ->willReturnMap([
                [$body['target_class_name'], $body['target_id'], $category],
                [Scope::class, $body['scope_id'], null]
            ]);
        $this->cacheBuilder->expects($this->never())
            ->method('resolveVisibilitySettings');

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Product Visibility resolve.',
                ['exception' => new EntityNotFoundException('Scope object object was not found.')]
            );

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessWithoutEntityIdAndTargetEntityNotFound()
    {
        $body = [
            'entity_class_name' => CategoryVisibility::class,
            'target_class_name' => Category::class,
            'target_id'         => 12,
            'scope_id'          => 1
        ];

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
                [CategoryVisibilityResolved::class, $em],
                [$body['target_class_name'], $em]
            ]);

        $em->expects($this->once())
            ->method('find')
            ->with($body['target_class_name'], $body['target_id'])
            ->willReturn(null);
        $this->cacheBuilder->expects($this->never())
            ->method('resolveVisibilitySettings');

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Product Visibility resolve.',
                ['exception' => new EntityNotFoundException('Target object was not found.')]
            );

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessDeadlock()
    {
        $body = ['entity_class_name' => CategoryVisibility::class, 'id' => 42];

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
                [CategoryVisibilityResolved::class, $em],
                [CategoryVisibility::class, $em]
            ]);

        $visibility = new CategoryVisibility();
        $em->expects($this->once())
            ->method('find')
            ->with(CategoryVisibility::class, $body['id'])
            ->willReturn($visibility);
        $this->cacheBuilder->expects($this->once())
            ->method('resolveVisibilitySettings')
            ->with($this->identicalTo($visibility))
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Product Visibility resolve.',
                ['exception' => $exception]
            );

        $this->assertEquals(
            MessageProcessorInterface::REQUEUE,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessException()
    {
        $body = ['entity_class_name' => CategoryVisibility::class, 'id' => 42];

        $exception = new \Exception('some error');

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
                [CategoryVisibilityResolved::class, $em],
                [CategoryVisibility::class, $em]
            ]);

        $visibility = new CategoryVisibility();
        $em->expects($this->once())
            ->method('find')
            ->with(CategoryVisibility::class, $body['id'])
            ->willReturn($visibility);
        $this->cacheBuilder->expects($this->once())
            ->method('resolveVisibilitySettings')
            ->with($this->identicalTo($visibility))
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Product Visibility resolve.',
                ['exception' => $exception]
            );

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }
}
