<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Visibility\Cache\Product;

use Oro\Bundle\AccountBundle\Visibility\Cache\Product\CacheBuilder;
use Oro\Bundle\AccountBundle\Visibility\Cache\ProductCaseCacheBuilderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;

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

    public function testProductCategoryChanged()
    {
        $product = new Product();

        /** @var \PHPUnit_Framework_MockObject_MockObject|ProductCaseCacheBuilderInterface $customBuilder */
        $customBuilder
            = $this->getMock('Oro\Bundle\AccountBundle\Visibility\Cache\ProductCaseCacheBuilderInterface');
        $customBuilder->expects($this->once())
            ->method('productCategoryChanged')
            ->with($product);

        $this->cacheBuilder->addBuilder($customBuilder);
        $this->cacheBuilder->productCategoryChanged($product);
    }
}
