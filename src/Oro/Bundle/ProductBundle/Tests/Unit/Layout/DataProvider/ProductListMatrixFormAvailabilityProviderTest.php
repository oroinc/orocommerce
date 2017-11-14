<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ProductFormAvailabilityProvider;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ProductListMatrixFormAvailabilityProvider;
use Oro\Bundle\UIBundle\Provider\UserAgent;
use Oro\Bundle\UIBundle\Provider\UserAgentProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductListMatrixFormAvailabilityProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    private $configManager;

    /** @var ProductFormAvailabilityProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $productFormAvailabilityProvider;

    /** @var UserAgent|\PHPUnit_Framework_MockObject_MockObject */
    private $userAgent;

    /** @var ProductListMatrixFormAvailabilityProvider */
    private $provider;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->productFormAvailabilityProvider = $this->createMock(ProductFormAvailabilityProvider::class);

        /** @var UserAgentProvider|\PHPUnit_Framework_MockObject_MockObject $userAgentProvider */
        $userAgentProvider = $this->createMock(UserAgentProvider::class);
        $this->userAgent = $this->createMock(UserAgent::class);
        $userAgentProvider->expects($this->once())
            ->method('getUserAgent')
            ->willReturn($this->userAgent);

        $this->provider = new ProductListMatrixFormAvailabilityProvider(
            $this->configManager,
            $this->productFormAvailabilityProvider,
            $userAgentProvider
        );
    }

    /**
     * @return array
     */
    public function isInlineMatrixFormAvailableDataProvider()
    {
        return [
            'is mobile' => [
                'isMobile' => true,
                'matrixFormConfig' => Configuration::MATRIX_FORM_ON_PRODUCT_LISTING_INLINE,
                'isMatrixFormAvailable' => true,
                'expected' => false,
            ],
            'config not inline' => [
                'isMobile' => false,
                'matrixFormConfig' => 'none',
                'isMatrixFormAvailable' => true,
                'expected' => false,
            ],
            'matrix form not available' => [
                'isMobile' => false,
                'matrixFormConfig' => Configuration::MATRIX_FORM_ON_PRODUCT_LISTING_INLINE,
                'isMatrixFormAvailable' => false,
                'expected' => false,
            ],
            'matrix form available' => [
                'isMobile' => false,
                'matrixFormConfig' => Configuration::MATRIX_FORM_ON_PRODUCT_LISTING_INLINE,
                'isMatrixFormAvailable' => true,
                'expected' => true,
            ],
        ];
    }

    /**
     * @param bool $isMobile
     * @param string $matrixFormConfig
     * @param bool $isMatrixFormAvailable
     * @param bool $expected
     * @dataProvider isInlineMatrixFormAvailableDataProvider
     */
    public function testIsInlineMatrixFormAvailable($isMobile, $matrixFormConfig, $isMatrixFormAvailable, $expected)
    {
        $this->userAgent->expects($this->once())
            ->method('isMobile')
            ->willReturn($isMobile);

        $this->configManager->expects($this->any())
            ->method('get')
            ->with(sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::MATRIX_FORM_ON_PRODUCT_LISTING))
            ->willReturn($matrixFormConfig);

        $product = $this->getEntity(Product::class);

        $this->productFormAvailabilityProvider->expects($this->any())
            ->method('isMatrixFormAvailable')
            ->with($product)
            ->willReturn($isMatrixFormAvailable);

        $this->assertEquals($expected, $this->provider->isInlineMatrixFormAvailable($product));
    }
}
