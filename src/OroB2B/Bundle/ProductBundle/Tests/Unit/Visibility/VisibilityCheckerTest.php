<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Visibility;

use Prophecy\Prophecy\ObjectProphecy;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Visibility\VisibilityChecker;

class VisibilityCheckerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var VisibilityChecker
     */
    protected $service;

    /**
     * @var ConfigManager|ObjectProphecy
     */
    protected $configManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->configManager = $this->prophesize('Oro\Bundle\ConfigBundle\Config\ConfigManager');

        $this->service = new VisibilityChecker($this->configManager->reveal());
    }

    public function testIsVisibleFromSystemConfig()
    {
        $systemConfigVisibility = Product::VISIBILITY_VISIBLE;
        $this->configManager->get('orob2b_product.default_visibility')->willReturn($systemConfigVisibility);

        $product = $this->prophesize('OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProduct');
        $product->getVisibility()->willReturn(Product::VISIBILITY_BY_CONFIG);

        $this->assertEquals(true, $this->service->isVisible($product->reveal()));
    }

    public function testIsVisibleFromEntity()
    {
        $product = $this->prophesize('OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProduct');
        $product->getVisibility()->willReturn(Product::VISIBILITY_NOT_VISIBLE);

        $this->assertEquals(false, $this->service->isVisible($product->reveal()));
    }
}
