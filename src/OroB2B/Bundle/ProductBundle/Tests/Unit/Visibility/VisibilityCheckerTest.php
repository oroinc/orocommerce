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
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->service = new VisibilityChecker($this->configManager);
    }

    /**
     * @dataProvider visibilityDataProvider
     * @param string $visibility
     * @param bool $expected
     */
    public function testChecksVisibilityFromConfig($visibility, $expected)
    {
        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('orob2b_product.default_visibility')
            ->willReturn($visibility);

        $product = $this->getProductMock();
        $product
            ->expects($this->once())
            ->method('getVisibility')
            ->willReturn(Product::VISIBILITY_BY_CONFIG);

        $this->assertEquals($expected, $this->service->isVisible($product));
    }

    /**
     * @dataProvider visibilityDataProvider
     * @param string $visibility
     * @param bool $expected
     */
    public function testChecksVisibilityFromEntity($visibility, $expected)
    {
        $product = $this->getProductMock();
        $product
            ->expects($this->any())
            ->method('getVisibility')
            ->willReturn($visibility);

        $this->assertEquals($expected, $this->service->isVisible($product));
    }

    /**
     * @return array
     */
    public function visibilityDataProvider()
    {
        return [
            [Product::VISIBILITY_VISIBLE, true],
            [Product::VISIBILITY_NOT_VISIBLE, false]
        ];
    }

    /**
     * @return Product|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getProductMock()
    {
        return $this->getMock('OroB2B\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProduct');
    }
}
