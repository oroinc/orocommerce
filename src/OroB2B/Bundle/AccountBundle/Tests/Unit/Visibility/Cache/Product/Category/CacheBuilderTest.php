<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Visibility\Cache\Product\Category;

use OroB2B\Bundle\AccountBundle\Tests\Unit\Visibility\Cache\Product\AbstractCacheBuilderTest;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\CacheBuilder;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\CategoryCaseCacheBuilderInterface;

class CacheBuilderTest extends AbstractCacheBuilderTest
{
    /**
     * @var CategoryCaseCacheBuilderInterface[]|\PHPUnit_Framework_MockObject_MockObject[]
     */
    protected $builders;

    /**
     * @var CacheBuilder
     */
    protected $cacheBuilder;

    /**
     * @var string
     */
    protected $cacheBuilderInterface = 'OroB2B\Bundle\AccountBundle\Visibility\Cache\CategoryCaseCacheBuilderInterface';

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

    public function testProductCategoryChanged()
    {
        $this->assertCallAllBuilders(
            'categoryPositionChanged',
            $this->getMock('OroB2B\Bundle\CatalogBundle\Entity\Category')
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
