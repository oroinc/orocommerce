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

class ProductFormAvailabilityProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    const CONFIG = 'matrix_form_on_product_view';

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ProductMatrixAvailabilityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $productMatrixAvailabilityProvider;

    /** @var UserAgent|\PHPUnit\Framework\MockObject\MockObject */
    private $userAgent;

    /** @var ProductFormAvailabilityProvider */
    private $provider;

    /** @var UserAgentProvider|\PHPUnit\Framework\MockObject\MockObject $userAgentProvider */
    private $userAgentProvider;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
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
     * @param bool $isMobile
     * @param string $matrixFormConfig
     * @param bool $isMatrixFormAvailable
     * @param string $productView
     * @param string $expectedAvailableMatrixFormType
     * @param bool $expectedIsMatrixFormAvailable
     * @param bool $expectedIsInlineMatrixFormAvailable
     *
     * @dataProvider getAvailableMatrixFormTypeDataProvider
     */
    public function testIsInlineMatrixFormAvailable(
        $isMobile,
        $matrixFormConfig,
        $isMatrixFormAvailable,
        $productView,
        $expectedAvailableMatrixFormType,
        $expectedIsMatrixFormAvailable,
        $expectedIsInlineMatrixFormAvailable
    ) {
        $product = $this->prepareProvider($isMobile, $matrixFormConfig, $isMatrixFormAvailable);

        if ($productView) {
            $this->assertEquals(
                $expectedIsInlineMatrixFormAvailable,
                $this->provider->isInlineMatrixFormAvailable($product, $productView)
            );
        } else {
            $this->assertEquals(
                $expectedIsInlineMatrixFormAvailable,
                $this->provider->isInlineMatrixFormAvailable($product)
            );
        }
    }

    /**
     * @param bool $isMobile
     * @param string $matrixFormConfig
     * @param bool $isMatrixFormAvailable
     * @param string $productView
     * @param string $expectedAvailableMatrixFormType
     * @param bool $expectedIsMatrixFormAvailable
     * @param bool $expectedIsInlineMatrixFormAvailable
     * @param bool $expectedIsPopupMatrixFormAvailable
     *
     * @dataProvider getAvailableMatrixFormTypeDataProvider
     */
    public function testIsPopupMatrixFormAvailable(
        $isMobile,
        $matrixFormConfig,
        $isMatrixFormAvailable,
        $productView,
        $expectedAvailableMatrixFormType,
        $expectedIsMatrixFormAvailable,
        $expectedIsInlineMatrixFormAvailable,
        $expectedIsPopupMatrixFormAvailable
    ) {
        $product = $this->prepareProvider($isMobile, $matrixFormConfig, $isMatrixFormAvailable);

        if ($productView) {
            $this->assertEquals(
                $expectedIsPopupMatrixFormAvailable,
                $this->provider->isPopupMatrixFormAvailable($product, $productView)
            );
        } else {
            $this->assertEquals(
                $expectedIsPopupMatrixFormAvailable,
                $this->provider->isPopupMatrixFormAvailable($product)
            );
        }
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getAvailableMatrixFormTypeDataProvider()
    {
        return [
            'desktop, config none, matrix not available' => [
                'isMobile' => false,
                'matrixFormConfig' => Configuration::MATRIX_FORM_NONE,
                'isMatrixFormAvailable' => false,
                'productView' => '',
                'expectedAvailableMatrixFormType' => Configuration::MATRIX_FORM_NONE,
                'expectedIsMatrixFormAvailable' => false,
                'expectedIsInlineMatrixFormAvailable' => false,
                'expectedIsPopupMatrixFormAvailable' => false,
            ],
            'desktop, config none, matrix available' => [
                'isMobile' => false,
                'matrixFormConfig' => Configuration::MATRIX_FORM_NONE,
                'isMatrixFormAvailable' => true,
                'productView' => '',
                'expectedAvailableMatrixFormType' => Configuration::MATRIX_FORM_NONE,
                'expectedIsMatrixFormAvailable' => false,
                'expectedIsInlineMatrixFormAvailable' => false,
                'expectedIsPopupMatrixFormAvailable' => false,
            ],
            'desktop, config popup, matrix not available' => [
                'isMobile' => false,
                'matrixFormConfig' => Configuration::MATRIX_FORM_POPUP,
                'isMatrixFormAvailable' => false,
                'productView' => '',
                'expectedAvailableMatrixFormType' => Configuration::MATRIX_FORM_NONE,
                'expectedIsMatrixFormAvailable' => false,
                'expectedIsInlineMatrixFormAvailable' => false,
                'expectedIsPopupMatrixFormAvailable' => false,
            ],
            'desktop, config popup, matrix available' => [
                'isMobile' => false,
                'matrixFormConfig' => Configuration::MATRIX_FORM_POPUP,
                'isMatrixFormAvailable' => true,
                'productView' => '',
                'expectedAvailableMatrixFormType' => Configuration::MATRIX_FORM_POPUP,
                'expectedIsMatrixFormAvailable' => true,
                'expectedIsInlineMatrixFormAvailable' => false,
                'expectedIsPopupMatrixFormAvailable' => true,
            ],
            'desktop, config inline, matrix not available' => [
                'isMobile' => false,
                'matrixFormConfig' => Configuration::MATRIX_FORM_INLINE,
                'isMatrixFormAvailable' => false,
                'productView' => '',
                'expectedAvailableMatrixFormType' => Configuration::MATRIX_FORM_NONE,
                'expectedIsMatrixFormAvailable' => false,
                'expectedIsInlineMatrixFormAvailable' => false,
                'expectedIsPopupMatrixFormAvailable' => false,
            ],
            'desktop, config inline, matrix available' => [
                'isMobile' => false,
                'matrixFormConfig' => Configuration::MATRIX_FORM_INLINE,
                'isMatrixFormAvailable' => true,
                'productView' => '',
                'expectedAvailableMatrixFormType' => Configuration::MATRIX_FORM_INLINE,
                'expectedIsMatrixFormAvailable' => true,
                'expectedIsInlineMatrixFormAvailable' => true,
                'expectedIsPopupMatrixFormAvailable' => false,
            ],
            'desktop, config inline, matrix available, gallery-view' => [
                'isMobile' => false,
                'matrixFormConfig' => Configuration::MATRIX_FORM_INLINE,
                'isMatrixFormAvailable' => true,
                'productView' => 'gallery-view',
                'expectedAvailableMatrixFormType' => Configuration::MATRIX_FORM_POPUP,
                'expectedIsMatrixFormAvailable' => true,
                'expectedIsInlineMatrixFormAvailable' => false,
                'expectedIsPopupMatrixFormAvailable' => true,
            ],
            'mobile, config none, matrix not available' => [
                'isMobile' => true,
                'matrixFormConfig' => Configuration::MATRIX_FORM_NONE,
                'isMatrixFormAvailable' => false,
                'productView' => '',
                'expectedAvailableMatrixFormType' => Configuration::MATRIX_FORM_NONE,
                'expectedIsMatrixFormAvailable' => false,
                'expectedIsInlineMatrixFormAvailable' => false,
                'expectedIsPopupMatrixFormAvailable' => false,
            ],
            'mobile, config none, matrix available' => [
                'isMobile' => true,
                'matrixFormConfig' => Configuration::MATRIX_FORM_NONE,
                'isMatrixFormAvailable' => true,
                'productView' => '',
                'expectedAvailableMatrixFormType' => Configuration::MATRIX_FORM_NONE,
                'expectedIsMatrixFormAvailable' => false,
                'expectedIsInlineMatrixFormAvailable' => false,
                'expectedIsPopupMatrixFormAvailable' => false,
            ],
            'mobile, config popup, matrix not available' => [
                'isMobile' => true,
                'matrixFormConfig' => Configuration::MATRIX_FORM_POPUP,
                'isMatrixFormAvailable' => false,
                'productView' => '',
                'expectedAvailableMatrixFormType' => Configuration::MATRIX_FORM_NONE,
                'expectedIsMatrixFormAvailable' => false,
                'expectedIsInlineMatrixFormAvailable' => false,
                'expectedIsPopupMatrixFormAvailable' => false,
            ],
            'mobile, config popup, matrix available' => [
                'isMobile' => true,
                'matrixFormConfig' => Configuration::MATRIX_FORM_POPUP,
                'isMatrixFormAvailable' => true,
                'productView' => '',
                'expectedAvailableMatrixFormType' => Configuration::MATRIX_FORM_POPUP,
                'expectedIsMatrixFormAvailable' => true,
                'expectedIsInlineMatrixFormAvailable' => false,
                'expectedIsPopupMatrixFormAvailable' => true,
            ],
            'mobile, config inline, matrix not available' => [
                'isMobile' => true,
                'matrixFormConfig' => Configuration::MATRIX_FORM_INLINE,
                'isMatrixFormAvailable' => false,
                'productView' => '',
                'expectedAvailableMatrixFormType' => Configuration::MATRIX_FORM_NONE,
                'expectedIsMatrixFormAvailable' => false,
                'expectedIsInlineMatrixFormAvailable' => false,
                'expectedIsPopupMatrixFormAvailable' => false,
            ],
            'mobile, config inline, matrix available' => [
                'isMobile' => true,
                'matrixFormConfig' => Configuration::MATRIX_FORM_INLINE,
                'isMatrixFormAvailable' => true,
                'productView' => '',
                'expectedAvailableMatrixFormType' => Configuration::MATRIX_FORM_POPUP,
                'expectedIsMatrixFormAvailable' => true,
                'expectedIsInlineMatrixFormAvailable' => false,
                'expectedIsPopupMatrixFormAvailable' => true,
            ],
        ];
    }

    /**
     * @param bool $isMobile
     * @param string $matrixFormConfig
     * @param bool $isMatrixFormAvailable
     * @param string $productView
     * @param bool $expectedAvailableMatrixFormType
     *
     * @dataProvider getAvailableMatrixFormTypeDataProvider
     */
    public function testGetAvailableMatrixFormType(
        $isMobile,
        $matrixFormConfig,
        $isMatrixFormAvailable,
        $productView,
        $expectedAvailableMatrixFormType
    ) {
        $product = $this->prepareProvider($isMobile, $matrixFormConfig, $isMatrixFormAvailable);

        if ($productView) {
            $this->assertEquals(
                $expectedAvailableMatrixFormType,
                $this->provider->getAvailableMatrixFormType($product, $productView)
            );
        } else {
            $this->assertEquals(
                $expectedAvailableMatrixFormType,
                $this->provider->getAvailableMatrixFormType($product)
            );
        }
    }

    /**
     * @param bool $isMobile
     * @param string $matrixFormConfig
     * @param bool $isMatrixFormAvailable
     * @param string $productView
     * @param bool $expectedAvailableMatrixFormType
     *
     * @dataProvider getAvailableMatrixFormTypeDataProvider
     */
    public function testGetAvailableMatrixFormTypes(
        $isMobile,
        $matrixFormConfig,
        $isMatrixFormAvailable,
        $productView,
        $expectedAvailableMatrixFormType
    ) {
        $product = $this->prepareProvider($isMobile, $matrixFormConfig, $isMatrixFormAvailable);

        if ($productView) {
            $this->assertEquals(
                [123 => $expectedAvailableMatrixFormType],
                $this->provider->getAvailableMatrixFormTypes([$product], $productView)
            );
        } else {
            $this->assertEquals(
                [123 => $expectedAvailableMatrixFormType],
                $this->provider->getAvailableMatrixFormTypes([$product])
            );
        }
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

        $product = $this->getEntity(Product::class, ['id' => 123]);

        $this->productMatrixAvailabilityProvider->expects($this->any())
            ->method('isMatrixFormAvailable')
            ->with($product)
            ->willReturn($isMatrixFormAvailable);

        return $product;
    }

    /**
     * @param bool $isMobile
     * @param string $matrixFormConfig
     * @param bool $isMatrixFormAvailable
     * @param string $productView
     * @param string $expectedAvailableMatrixFormType
     * @param bool $expectedIsMatrixFormAvailable
     *
     * @dataProvider getAvailableMatrixFormTypeDataProvider
     */
    public function testIsMatrixFormAvailable(
        $isMobile,
        $matrixFormConfig,
        $isMatrixFormAvailable,
        $productView,
        $expectedAvailableMatrixFormType,
        $expectedIsMatrixFormAvailable
    ) {
        $product = $this->prepareProvider($isMobile, $matrixFormConfig, $isMatrixFormAvailable);

        if ($productView) {
            $this->assertEquals(
                $expectedIsMatrixFormAvailable,
                $this->provider->isMatrixFormAvailable($product, $productView)
            );
            $this->assertEquals(
                !$expectedIsMatrixFormAvailable,
                $this->provider->isSimpleFormAvailable($product, $productView)
            );
        } else {
            $this->assertEquals(
                $expectedIsMatrixFormAvailable,
                $this->provider->isMatrixFormAvailable($product)
            );
            $this->assertEquals(
                !$expectedIsMatrixFormAvailable,
                $this->provider->isSimpleFormAvailable($product)
            );
        }
    }
}
