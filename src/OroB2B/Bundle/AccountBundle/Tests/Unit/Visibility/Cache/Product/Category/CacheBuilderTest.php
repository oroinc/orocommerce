<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Visibility\Cache\Product\Category;

use OroB2B\Bundle\AccountBundle\Tests\Unit\Visibility\Cache\Product\AbstractCacheBuilderTest;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\CacheBuilder;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\cacheBuilderInterface;

class CacheBuilderTest extends AbstractCacheBuilderTest
{
    /**
     * @var cacheBuilderInterface[]|\PHPUnit_Framework_MockObject_MockObject[]
     */
    protected $builders;

    /**
     * @var CacheBuilder
     */
    protected $cacheBuilder;

    /**
     * @var string
     */
    protected $cacheBuilderInterface = 'OroB2B\Bundle\AccountBundle\Visibility\Cache\CacheBuilderInterface';

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
}
