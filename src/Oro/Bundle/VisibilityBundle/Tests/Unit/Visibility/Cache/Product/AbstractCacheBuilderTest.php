<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Visibility\Cache\Product;

use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\CacheBuilderInterface;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\CacheBuilder as ProductCaseCacheBuilder;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\CacheBuilder as CategoryCaseCacheBuilder;

abstract class AbstractCacheBuilderTest extends \PHPUnit\Framework\TestCase
{
    /** @var CacheBuilderInterface[]|\PHPUnit\Framework\MockObject\MockObject[] */
    protected $builders;

    /** @var ProductCaseCacheBuilder|CategoryCaseCacheBuilder */
    protected $cacheBuilder;

    /** @var string */
    protected $cacheBuilderInterface = CacheBuilderInterface::class;

    protected function setUp(): void
    {
        $this->builders = [
            $this->createMock($this->cacheBuilderInterface),
            $this->createMock($this->cacheBuilderInterface),
            $this->createMock($this->cacheBuilderInterface)
        ];
    }

    public function testAddBuilder()
    {
        foreach ($this->builders as $builder) {
            $this->cacheBuilder->addBuilder($builder);
        }

        $this->assertCallAllBuilders(
            'buildCache',
            $this->createMock(Scope::class)
        );
    }

    public function testResolveVisibilitySettings()
    {
        $visibility = $this->createMock(VisibilityInterface::class);
        $concreteBuilder = $this->createMock($this->cacheBuilderInterface);

        $concreteBuilder->expects($this->once())
            ->method('isVisibilitySettingsSupported')
            ->with($visibility)
            ->willReturn(true);

        $concreteBuilder->expects($this->once())
            ->method('resolveVisibilitySettings')
            ->with($visibility);

        $this->cacheBuilder->addBuilder($concreteBuilder);

        foreach ($this->builders as $builder) {
            $builder->expects($this->once())
                ->method('isVisibilitySettingsSupported')
                ->with($visibility)
                ->willReturn(false);
        }

        $this->assertCallAllBuilders('resolveVisibilitySettings', $visibility, 0);
    }

    public function testIsVisibilitySettingsSupportedFalse()
    {
        $result = $this->assertCallAllBuilders(
            'isVisibilitySettingsSupported',
            $this->createMock(VisibilityInterface::class)
        );

        $this->assertFalse($result);
    }

    /**
     * @depends testIsVisibilitySettingsSupportedFalse
     */
    public function testIsVisibilitySettingsSupported()
    {
        $concreteBuilder = $this->createMock($this->cacheBuilderInterface);

        $concreteBuilder->expects($this->once())
            ->method('isVisibilitySettingsSupported')
            ->willReturn(true);

        $this->cacheBuilder->addBuilder($concreteBuilder);

        $result = $this->assertCallAllBuilders(
            'isVisibilitySettingsSupported',
            $this->createMock(VisibilityInterface::class)
        );

        $this->assertTrue($result);
    }

    public function testBuildCache()
    {
        $this->assertCallAllBuilders('buildCache', $this->createMock(Scope::class));
    }

    protected function assertCallAllBuilders(string $method, mixed $argument, int $callCount = 1): mixed
    {
        foreach ($this->builders as $builder) {
            $builder->expects($this->exactly($callCount))
                ->method($method)
                ->with($argument);
        }

        return call_user_func([$this->cacheBuilder, $method], $argument);
    }
}
