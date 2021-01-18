<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Resolver;

use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\WebsiteSearchBundle\Event\CollectDependentClassesEvent;
use Oro\Bundle\WebsiteSearchBundle\Resolver\EntityDependenciesResolver;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EntityDependenciesResolverTest extends \PHPUnit\Framework\TestCase
{
    /** @var SearchMappingProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $mappingProvider;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var EntityDependenciesResolver */
    private $entityDependenciesResolver;

    protected function setUp(): void
    {
        $this->mappingProvider = $this->getMockBuilder(SearchMappingProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityDependenciesResolver = new EntityDependenciesResolver(
            $this->eventDispatcher,
            $this->mappingProvider
        );
    }

    protected function tearDown(): void
    {
        unset($this->mappingProvider, $this->eventDispatcher, $this->entityDependenciesResolver);
    }

    public function testGetClassesForReindexWhenAllClassesReturned()
    {
        $expectedClasses = ['Product', 'Category', 'User'];

        $this->mappingProvider
            ->expects($this->once())
            ->method('getEntityClasses')
            ->willReturn($expectedClasses);

        $this->eventDispatcher
            ->expects($this->never())
            ->method('dispatch');

        $this->assertEquals($expectedClasses, $this->entityDependenciesResolver->getClassesForReindex());
    }

    public function testGetClassesForReindexWithDependentClasses()
    {
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(CollectDependentClassesEvent::class), CollectDependentClassesEvent::NAME)
            ->willReturnCallback(function (CollectDependentClassesEvent $event, $eventName) {
                $event->addClassDependencies('Product', ['Category', 'User']);
            });

        $this->assertEquals(['User', 'Product'], $this->entityDependenciesResolver->getClassesForReindex('User'));
    }

    public function testGetClassesForReindexWithCircularDependency()
    {
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(CollectDependentClassesEvent::class), CollectDependentClassesEvent::NAME)
            ->willReturnCallback(function (CollectDependentClassesEvent $event, $eventName) {
                $event->addClassDependencies('User', ['Category']);
                $event->addClassDependencies('Category', ['Product']);
                $event->addClassDependencies('Product', ['User']);
            });

        $this->assertEquals(
            ['User', 'Product', 'Category'],
            $this->entityDependenciesResolver->getClassesForReindex('User')
        );

        $this->assertEquals(
            ['Category', 'User', 'Product'],
            $this->entityDependenciesResolver->getClassesForReindex('Category')
        );

        $this->assertEquals(
            ['Product', 'Category', 'User'],
            $this->entityDependenciesResolver->getClassesForReindex('Product')
        );
    }
}
