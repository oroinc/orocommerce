<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Visibility\Cache\Product;

use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\CacheBuilderInterface;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\CacheBuilder as ProductCaseCacheBuilder;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\CacheBuilder as CategoryCaseCacheBuilder;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\ProductCaseCacheBuilderInterface;

abstract class AbstractCacheBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CacheBuilderInterface[]|\PHPUnit_Framework_MockObject_MockObject[]
     */
    protected $builders;

    /**
     * @var ProductCaseCacheBuilder|CategoryCaseCacheBuilder
     */
    protected $cacheBuilder;

    /**
     * @var string
     */
    protected $cacheBuilderInterface = 'Oro\Bundle\VisibilityBundle\Visibility\Cache\CacheBuilderInterface';

    protected function setUp()
    {
        $this->builders = [
            $this->getMock($this->cacheBuilderInterface),
            $this->getMock($this->cacheBuilderInterface),
            $this->getMock($this->cacheBuilderInterface)
        ];
    }

    public function testAddBuilder()
    {
        foreach ($this->builders as $builder) {
            $this->cacheBuilder->addBuilder($builder);
        }

        $this->assertCallAllBuilders(
            'buildCache',
            $this->getMock(Scope::class)
        );
    }

    public function testResolveVisibilitySettings()
    {
        $mock = $this->getMock('Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface');
        $concreteBuilder = $this->getMock($this->cacheBuilderInterface);

        $concreteBuilder->expects($this->once())
            ->method('isVisibilitySettingsSupported')
            ->with($mock)
            ->willReturn(true);

        $concreteBuilder->expects($this->once())
            ->method('resolveVisibilitySettings')
            ->with($mock);

        /** @var CacheBuilderInterface|ProductCaseCacheBuilderInterface $concreteBuilder */
        $this->cacheBuilder->addBuilder($concreteBuilder);

        foreach ($this->builders as $builder) {
            $builder->expects($this->once())
                ->method('isVisibilitySettingsSupported')
                ->with($mock)
                ->willReturn(false);
        }

        $this->assertCallAllBuilders('resolveVisibilitySettings', $mock, 0);
    }

    public function testIsVisibilitySettingsSupportedFalse()
    {
        $result = $this->assertCallAllBuilders(
            'isVisibilitySettingsSupported',
            $this->getMock('Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface')
        );

        $this->assertFalse($result);
    }

    /**
     * @depends testIsVisibilitySettingsSupportedFalse
     */
    public function testIsVisibilitySettingsSupported()
    {
        $concreteBuilder = $this->getMock($this->cacheBuilderInterface);

        $concreteBuilder->expects($this->once())
            ->method('isVisibilitySettingsSupported')
            ->willReturn(true);

        /** @var CacheBuilderInterface|ProductCaseCacheBuilderInterface $concreteBuilder */
        $this->cacheBuilder->addBuilder($concreteBuilder);

        $result = $this->assertCallAllBuilders(
            'isVisibilitySettingsSupported',
            $this->getMock('Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface')
        );

        $this->assertTrue($result);
    }

    public function testBuildCache()
    {
        $this->assertCallAllBuilders('buildCache', $this->getMock(Scope::class));
    }

    /**
     * @param string $method
     * @param mixed $argument
     * @param int $callCount
     * @return mixed
     */
    protected function assertCallAllBuilders($method, $argument, $callCount = 1)
    {
        foreach ($this->builders as $builder) {
            $builder->expects($this->exactly($callCount))
                ->method($method)
                ->with($argument);
        }

        return call_user_func([$this->cacheBuilder, $method], $argument);
    }
}
