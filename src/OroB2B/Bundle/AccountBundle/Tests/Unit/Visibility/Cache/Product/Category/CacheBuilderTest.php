<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Visibility\Cache\Product\Category;

use OroB2B\Bundle\AccountBundle\Tests\Unit\Visibility\Cache\Product\AbstractCacheBuilderTest;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\CategoryCaseCacheBuilderInterface;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\CacheBuilder;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class CacheBuilderTest extends AbstractCacheBuilderTest
{
    /**
     * @var CacheBuilder
     */
    protected $cacheBuilder;

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
        $category = new Category();

        /** @var \PHPUnit_Framework_MockObject_MockObject|CategoryCaseCacheBuilderInterface $customBuilder */
        $customBuilder
            = $this->getMock('OroB2B\Bundle\AccountBundle\Visibility\Cache\CategoryCaseCacheBuilderInterface');
        $customBuilder->expects($this->once())
            ->method('categoryPositionChanged')
            ->with($category);

        $this->cacheBuilder->addBuilder($customBuilder);
        $this->cacheBuilder->categoryPositionChanged($category);
    }
}
