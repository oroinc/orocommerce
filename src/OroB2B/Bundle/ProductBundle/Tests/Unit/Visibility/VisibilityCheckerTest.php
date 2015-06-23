<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Visibility;

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
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager', ['get'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->service = new VisibilityChecker($this->configManager);
    }

    public function testChecksVisibilityFromConfig()
    {
        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('orob2b_product.default_visibility')
            ->willReturn(Product::VISIBILITY_VISIBLE);

        $product = $this->getProductMock();
        $product
            ->expects($this->once())
            ->method('getVisibility')
            ->willReturn(Product::VISIBILITY_BY_CONFIG);

        $this->assertTrue($this->service->isVisible($product));
    }

    public function testChecksVisibilityFromEntity()
    {
        $product = $this->getProductMock();
        $product
            ->expects($this->any())
            ->method('getVisibility')
            ->willReturn(Product::VISIBILITY_NOT_VISIBLE);

        $this->assertFalse($this->service->isVisible($product));
    }

    /**
     * @return Product|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getProductMock()
    {
        return $this->getMock('OroB2B\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProduct');
    }
}
