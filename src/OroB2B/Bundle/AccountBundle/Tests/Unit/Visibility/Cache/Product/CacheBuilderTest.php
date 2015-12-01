<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Visibility\Cache\Product;

use OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\CacheBuilder;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\ProductCaseCacheBuilderInterface;

class CacheBuilderTest extends AbstractCacheBuilderTest
{
    /**
     * @var ProductCaseCacheBuilderInterface[]|\PHPUnit_Framework_MockObject_MockObject[]
     */
    protected $builders;

    /**
     * @var CacheBuilder
     */
    protected $cacheBuilder;

    /**
     * @var string
     */
    protected $cacheBuilderInterface = 'OroB2B\Bundle\AccountBundle\Visibility\Cache\ProductCaseCacheBuilderInterface';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->cacheBuilder = new CacheBuilder();

        foreach ($this->builders as $builder) {
            $this->cacheBuilder->addBuilder($builder);
        }
    }

    public function testAddBuilder()
    {
        foreach ($this->builders as $builder) {
            $this->cacheBuilder->addBuilder($builder);
        }

        $this->assertCallAllBuilders(
            'buildCache',
            $this->getMock('OroB2B\Bundle\WebsiteBundle\Entity\Website')
        );
    }

    public function testResolveVisibilitySettings()
    {
        $mock = $this->getMock('OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface');

        /** @var ProductCaseCacheBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->getMock('OroB2B\Bundle\AccountBundle\Visibility\Cache\ProductCaseCacheBuilderInterface');

        $builder->expects($this->once())
            ->method('isVisibilitySettingsSupported')
            ->with($mock)
            ->willReturn(true);

        $builder->expects($this->once())
            ->method('resolveVisibilitySettings')
            ->with($mock);

        $this->cacheBuilder->addBuilder($builder);

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
            $this->getMock('OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface')
        );

        $this->assertFalse($result);
    }

    /**
     * @depends testIsVisibilitySettingsSupportedFalse
     */
    public function testIsVisibilitySettingsSupported()
    {
        /** @var ProductCaseCacheBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->getMock('OroB2B\Bundle\AccountBundle\Visibility\Cache\ProductCaseCacheBuilderInterface');

        $builder->expects($this->once())
            ->method('isVisibilitySettingsSupported')
            ->willReturn(true);

        $this->cacheBuilder->addBuilder($builder);

        $result = $this->assertCallAllBuilders(
            'isVisibilitySettingsSupported',
            $this->getMock('OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface')
        );

        $this->assertTrue($result);
    }

    public function testBuildCache()
    {
        $this->assertCallAllBuilders('buildCache', $this->getMock('OroB2B\Bundle\WebsiteBundle\Entity\Website'));
    }

    public function testProductCategoryChanged()
    {
        $this->assertCallAllBuilders(
            'productCategoryChanged',
            $this->getMock('OroB2B\Bundle\ProductBundle\Entity\Product')
        );
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
