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

    public function testProductCategoryChanged()
    {
        $this->assertCallAllBuilders(
            'productCategoryChanged',
            $this->getMock('OroB2B\Bundle\ProductBundle\Entity\Product')
        );
    }
}
