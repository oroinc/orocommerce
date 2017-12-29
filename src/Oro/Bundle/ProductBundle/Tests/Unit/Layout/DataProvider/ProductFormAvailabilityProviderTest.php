<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ProductFormAvailabilityProvider;
use Oro\Bundle\ProductBundle\Provider\ProductMatrixAvailabilityProvider;
use Oro\Bundle\UIBundle\Provider\UserAgent;
use Oro\Bundle\UIBundle\Provider\UserAgentProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductFormAvailabilityProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const CONFIG = 'matrix_form_on_product_view';

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    private $configManager;

    /** @var ProductMatrixAvailabilityProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $productMatrixAvailabilityProvider;

    /** @var UserAgent|\PHPUnit_Framework_MockObject_MockObject */
    private $userAgent;

    /** @var ProductFormAvailabilityProvider */
    private $provider;

    /** @var UserAgentProvider|\PHPUnit_Framework_MockObject_MockObject $userAgentProvider */
    private $userAgentProvider;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->productMatrixAvailabilityProvider = $this->createMock(ProductMatrixAvailabilityProvider::class);

        $this->userAgentProvider = $this->createMock(UserAgentProvider::class);
        $this->userAgent = $this->createMock(UserAgent::class);

        $this->provider = new ProductFormAvailabilityProvider(
            $this->configManager,
            $this->productMatrixAvailabilityProvider,
            $this->userAgentProvider
        );
        $this->provider->setMatrixFormConfig(Configuration::MATRIX_FORM_ON_PRODUCT_VIEW);
    }

    /**
     * @return array
     */
    public function isInlineMatrixFormAvailableDataProvider()
    {
        return [
            'is mobile' => [
                'isMobile' => true,
                'matrixFormConfig' => Configuration::MATRIX_FORM_INLINE,
                'isMatrixFormAvailable' => true,
                'expected' => false,
            ],
            'config not inline' => [
                'isMobile' => false,
                'matrixFormConfig' => Configuration::MATRIX_FORM_NONE,
                'isMatrixFormAvailable' => true,
                'expected' => false,
            ],
            'matrix form not available' => [
                'isMobile' => false,
                'matrixFormConfig' => Configuration::MATRIX_FORM_INLINE,
                'isMatrixFormAvailable' => false,
                'expected' => false,
            ],
            'matrix form available' => [
                'isMobile' => false,
                'matrixFormConfig' => Configuration::MATRIX_FORM_INLINE,
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
        $product = $this->prepareProvider($isMobile, $matrixFormConfig, $isMatrixFormAvailable);

        $this->assertEquals($expected, $this->provider->isInlineMatrixFormAvailable($product));
    }

    /**
     * @return array
     */
    public function getAvailableMatrixFormTypeDataProvider()
    {
        return [
            'desktop, config none, matrix not available' => [
                'isMobile' => false,
                'matrixFormConfig' => Configuration::MATRIX_FORM_NONE,
                'isMatrixFormAvailable' => false,
                'expected' => Configuration::MATRIX_FORM_NONE,
            ],
            'desktop, config none, matrix available' => [
                'isMobile' => false,
                'matrixFormConfig' => Configuration::MATRIX_FORM_NONE,
                'isMatrixFormAvailable' => true,
                'expected' => Configuration::MATRIX_FORM_NONE,
            ],
            'desktop, config popup, matrix not available' => [
                'isMobile' => false,
                'matrixFormConfig' => Configuration::MATRIX_FORM_POPUP,
                'isMatrixFormAvailable' => false,
                'expected' => Configuration::MATRIX_FORM_NONE,
            ],
            'desktop, config popup, matrix available' => [
                'isMobile' => false,
                'matrixFormConfig' => Configuration::MATRIX_FORM_POPUP,
                'isMatrixFormAvailable' => true,
                'expected' => Configuration::MATRIX_FORM_POPUP,
            ],
            'desktop, config inline, matrix not available' => [
                'isMobile' => false,
                'matrixFormConfig' => Configuration::MATRIX_FORM_INLINE,
                'isMatrixFormAvailable' => false,
                'expected' => Configuration::MATRIX_FORM_NONE,
            ],
            'desktop, config inline, matrix available' => [
                'isMobile' => false,
                'matrixFormConfig' => Configuration::MATRIX_FORM_INLINE,
                'isMatrixFormAvailable' => true,
                'expected' => Configuration::MATRIX_FORM_INLINE,
            ],
            'mobile, config none, matrix not available' => [
                'isMobile' => true,
                'matrixFormConfig' => Configuration::MATRIX_FORM_NONE,
                'isMatrixFormAvailable' => false,
                'expected' => Configuration::MATRIX_FORM_NONE,
            ],
            'mobile, config none, matrix available' => [
                'isMobile' => true,
                'matrixFormConfig' => Configuration::MATRIX_FORM_NONE,
                'isMatrixFormAvailable' => true,
                'expected' => Configuration::MATRIX_FORM_NONE,
            ],
            'mobile, config popup, matrix not available' => [
                'isMobile' => true,
                'matrixFormConfig' => Configuration::MATRIX_FORM_POPUP,
                'isMatrixFormAvailable' => false,
                'expected' => Configuration::MATRIX_FORM_NONE,
            ],
            'mobile, config popup, matrix available' => [
                'isMobile' => true,
                'matrixFormConfig' => Configuration::MATRIX_FORM_POPUP,
                'isMatrixFormAvailable' => true,
                'expected' => Configuration::MATRIX_FORM_POPUP,
            ],
            'mobile, config inline, matrix not available' => [
                'isMobile' => true,
                'matrixFormConfig' => Configuration::MATRIX_FORM_INLINE,
                'isMatrixFormAvailable' => false,
                'expected' => Configuration::MATRIX_FORM_NONE,
            ],
            'mobile, config inline, matrix available' => [
                'isMobile' => true,
                'matrixFormConfig' => Configuration::MATRIX_FORM_INLINE,
                'isMatrixFormAvailable' => true,
                'expected' => Configuration::MATRIX_FORM_POPUP,
            ],
        ];
    }

    /**
     * @param bool $isMobile
     * @param string $matrixFormConfig
     * @param bool $isMatrixFormAvailable
     * @param bool $expected
     * @dataProvider getAvailableMatrixFormTypeDataProvider
     */
    public function testGetAvailableMatrixFormType($isMobile, $matrixFormConfig, $isMatrixFormAvailable, $expected)
    {
        $product = $this->prepareProvider($isMobile, $matrixFormConfig, $isMatrixFormAvailable);

        $this->assertEquals($expected, $this->provider->getAvailableMatrixFormType($product));
    }

    /**
     * @param bool $isMobile
     * @param string $matrixFormConfig
     * @param bool $isMatrixFormAvailable
     * @return Product
     */
    private function prepareProvider($isMobile, $matrixFormConfig, $isMatrixFormAvailable)
    {
        $this->userAgentProvider->expects($this->any())
            ->method('getUserAgent')
            ->willReturn($this->userAgent);

        $this->userAgent->expects($this->any())
            ->method('isMobile')
            ->willReturn($isMobile);

        $this->configManager->expects($this->any())
            ->method('get')
            ->with(sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::MATRIX_FORM_ON_PRODUCT_VIEW))
            ->willReturn($matrixFormConfig);

        $product = $this->getEntity(Product::class);

        $this->productMatrixAvailabilityProvider->expects($this->any())
            ->method('isMatrixFormAvailable')
            ->with($product)
            ->willReturn($isMatrixFormAvailable);

        return $product;
    }

    /**
     * @return array
     */
    public function isMatrixFormAvailableDataProvider()
    {
        return [
            'inline matrix' => [
                'matrixFormConfig' => Configuration::MATRIX_FORM_INLINE,
                'isMatrixFormAvailable' => true,
                'isMobile' => false,
                'expected' => true,
            ],
            'popup matrix' => [
                'matrixFormConfig' => Configuration::MATRIX_FORM_POPUP,
                'isMatrixFormAvailable' => true,
                'isMobile' => false,
                'expected' => true,
            ],
            'no matrix' => [
                'matrixFormConfig' => Configuration::MATRIX_FORM_INLINE,
                'isMatrixFormAvailable' => false,
                'isMobile' => false,
                'expected' => false,
            ],
        ];
    }

    /**
     * @param string $matrixFormConfig
     * @param bool $isMatrixFormAvailable
     * @param bool $isMobile
     * @param bool $expected
     * @dataProvider isMatrixFormAvailableDataProvider
     */
    public function testIsMatrixFormAvailable($matrixFormConfig, $isMatrixFormAvailable, $isMobile, $expected)
    {
        $configurableProduct = $this->getEntity(Product::class);

        $this->configManager->expects($this->any())
            ->method('get')
            ->with(sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::MATRIX_FORM_ON_PRODUCT_VIEW))
            ->willReturn($matrixFormConfig);

        $this->productMatrixAvailabilityProvider->expects($this->exactly(2))
            ->method('isMatrixFormAvailable')
            ->with($configurableProduct)
            ->willReturn($isMatrixFormAvailable);

        $this->userAgentProvider->expects($this->any())
            ->method('getUserAgent')
            ->willReturn($this->userAgent);

        $this->userAgent->expects($this->any())
            ->method('isMobile')
            ->willReturn($isMobile);

        $this->assertEquals($expected, $this->provider->isMatrixFormAvailable($configurableProduct));
        $this->assertEquals(!$expected, $this->provider->isSimpleFormAvailable($configurableProduct));
    }
}
