<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ProductViewFormAvailabilityProvider;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\ProductBundle\Provider\ProductFormAvailabilityProvider;
use Oro\Bundle\ProductBundle\Provider\ProductMatrixAvailabilityProvider;
use Oro\Bundle\UIBundle\Provider\UserAgent;
use Oro\Bundle\UIBundle\Provider\UserAgentProvider;
use Oro\Component\Testing\ReflectionUtil;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductViewFormAvailabilityProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductFormAvailabilityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $productFormAvailabilityProvider;

    /** @var ProductMatrixAvailabilityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $productMatrixAvailabilityProvider;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var UserAgentProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $userAgentProvider;

    /** @var ProductViewFormAvailabilityProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->productFormAvailabilityProvider = $this->createMock(ProductFormAvailabilityProvider::class);
        $this->productMatrixAvailabilityProvider = $this->createMock(ProductMatrixAvailabilityProvider::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->userAgentProvider = $this->createMock(UserAgentProvider::class);

        $this->provider = new ProductViewFormAvailabilityProvider(
            $this->productFormAvailabilityProvider,
            $this->productMatrixAvailabilityProvider,
            $this->configManager,
            $this->userAgentProvider
        );
    }

    private function setExpectationsForGetAvailableMatrixFormType(
        bool $isMobile,
        string $configuredMatrixFormType,
        bool $isMatrixFormAvailable
    ): Product {
        $product = new Product();
        ReflectionUtil::setId($product, 123);

        $this->configManager->expects(self::any())
            ->method('get')
            ->with('oro_product.matrix_form_on_product_view')
            ->willReturn($configuredMatrixFormType);

        $this->productMatrixAvailabilityProvider->expects(self::any())
            ->method('isMatrixFormAvailable')
            ->with($product)
            ->willReturn($isMatrixFormAvailable);

        $userAgent = $this->createMock(UserAgent::class);
        $userAgent->expects(self::any())
            ->method('isMobile')
            ->willReturn($isMobile);
        $this->userAgentProvider->expects(self::any())
            ->method('getUserAgent')
            ->willReturn($userAgent);

        return $product;
    }

    private function setExpectationsForGetAvailableMatrixFormTypeForProductView(?string $matrixFormType): ProductView
    {
        $product = new ProductView();
        $product->set('id', 123);
        $product->set('type', Product::TYPE_CONFIGURABLE);
        $product->set('unit', 'set');
        $product->set('variant_fields_count', 1);

        $availableMatrixFormTypes = [];
        if (null !== $matrixFormType) {
            $availableMatrixFormTypes = [$product->getId() => $matrixFormType];
        }
        $this->productFormAvailabilityProvider->expects(self::once())
            ->method('getAvailableMatrixFormTypes')
            ->with([$product->getId() => [$product->get('unit'), $product->get('variant_fields_count')]])
            ->willReturn($availableMatrixFormTypes);

        return $product;
    }

    /**
     * @dataProvider getAvailableMatrixFormTypeDataProvider
     */
    public function testIsSimpleFormAvailable(
        bool $isMobile,
        string $configuredMatrixFormType,
        bool $isMatrixFormAvailable,
        array $expected
    ): void {
        $product = $this->setExpectationsForGetAvailableMatrixFormType(
            $isMobile,
            $configuredMatrixFormType,
            $isMatrixFormAvailable
        );

        self::assertSame(
            $expected['isSimpleFormAvailable'],
            $this->provider->isSimpleFormAvailable($product)
        );
    }

    /**
     * @dataProvider getAvailableMatrixFormTypeDataProvider
     */
    public function testIsInlineMatrixFormAvailable(
        bool $isMobile,
        string $configuredMatrixFormType,
        bool $isMatrixFormAvailable,
        array $expected
    ): void {
        $product = $this->setExpectationsForGetAvailableMatrixFormType(
            $isMobile,
            $configuredMatrixFormType,
            $isMatrixFormAvailable
        );

        self::assertSame(
            $expected['isInlineMatrixFormAvailable'],
            $this->provider->isInlineMatrixFormAvailable($product)
        );
    }

    /**
     * @dataProvider getAvailableMatrixFormTypeDataProvider
     */
    public function testIsPopupMatrixFormAvailable(
        bool $isMobile,
        string $configuredMatrixFormType,
        bool $isMatrixFormAvailable,
        array $expected
    ): void {
        $product = $this->setExpectationsForGetAvailableMatrixFormType(
            $isMobile,
            $configuredMatrixFormType,
            $isMatrixFormAvailable
        );

        self::assertSame(
            $expected['isPopupMatrixFormAvailable'],
            $this->provider->isPopupMatrixFormAvailable($product)
        );
    }

    /**
     * @dataProvider getAvailableMatrixFormTypeDataProvider
     */
    public function testIsMatrixFormAvailable(
        bool $isMobile,
        string $configuredMatrixFormType,
        bool $isMatrixFormAvailable,
        array $expected
    ): void {
        $product = $this->setExpectationsForGetAvailableMatrixFormType(
            $isMobile,
            $configuredMatrixFormType,
            $isMatrixFormAvailable
        );

        self::assertSame(
            $expected['isMatrixFormAvailable'],
            $this->provider->isMatrixFormAvailable($product)
        );
    }

    /**
     * @dataProvider getAvailableMatrixFormTypeDataProvider
     */
    public function testGetAvailableMatrixFormType(
        bool $isMobile,
        string $configuredMatrixFormType,
        bool $isMatrixFormAvailable,
        array $expected
    ): void {
        $product = $this->setExpectationsForGetAvailableMatrixFormType(
            $isMobile,
            $configuredMatrixFormType,
            $isMatrixFormAvailable
        );

        self::assertSame(
            $expected['matrixFormType'],
            $this->provider->getAvailableMatrixFormType($product)
        );
    }


    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getAvailableMatrixFormTypeDataProvider(): array
    {
        return [
            'desktop, config none, matrix not available'   => [
                'isMobile'                 => false,
                'configuredMatrixFormType' => Configuration::MATRIX_FORM_NONE,
                'isMatrixFormAvailable'    => false,
                'expected'                 => [
                    'matrixFormType'              => Configuration::MATRIX_FORM_NONE,
                    'isMatrixFormAvailable'       => false,
                    'isSimpleFormAvailable'       => true,
                    'isInlineMatrixFormAvailable' => false,
                    'isPopupMatrixFormAvailable'  => false
                ]
            ],
            'desktop, config none, matrix available'       => [
                'isMobile'                 => false,
                'configuredMatrixFormType' => Configuration::MATRIX_FORM_NONE,
                'isMatrixFormAvailable'    => true,
                'expected'                 => [
                    'matrixFormType'              => Configuration::MATRIX_FORM_NONE,
                    'isMatrixFormAvailable'       => false,
                    'isSimpleFormAvailable'       => true,
                    'isInlineMatrixFormAvailable' => false,
                    'isPopupMatrixFormAvailable'  => false
                ]
            ],
            'desktop, config popup, matrix not available'  => [
                'isMobile'                 => false,
                'configuredMatrixFormType' => Configuration::MATRIX_FORM_POPUP,
                'isMatrixFormAvailable'    => false,
                'expected'                 => [
                    'matrixFormType'              => Configuration::MATRIX_FORM_NONE,
                    'isMatrixFormAvailable'       => false,
                    'isSimpleFormAvailable'       => true,
                    'isInlineMatrixFormAvailable' => false,
                    'isPopupMatrixFormAvailable'  => false
                ]
            ],
            'desktop, config popup, matrix available'      => [
                'isMobile'                 => false,
                'configuredMatrixFormType' => Configuration::MATRIX_FORM_POPUP,
                'isMatrixFormAvailable'    => true,
                'expected'                 => [
                    'matrixFormType'              => Configuration::MATRIX_FORM_POPUP,
                    'isMatrixFormAvailable'       => true,
                    'isSimpleFormAvailable'       => false,
                    'isInlineMatrixFormAvailable' => false,
                    'isPopupMatrixFormAvailable'  => true
                ]
            ],
            'desktop, config inline, matrix not available' => [
                'isMobile'                 => false,
                'configuredMatrixFormType' => Configuration::MATRIX_FORM_INLINE,
                'isMatrixFormAvailable'    => false,
                'expected'                 => [
                    'matrixFormType'              => Configuration::MATRIX_FORM_NONE,
                    'isMatrixFormAvailable'       => false,
                    'isSimpleFormAvailable'       => true,
                    'isInlineMatrixFormAvailable' => false,
                    'isPopupMatrixFormAvailable'  => false
                ]
            ],
            'desktop, config inline, matrix available'     => [
                'isMobile'                 => false,
                'configuredMatrixFormType' => Configuration::MATRIX_FORM_INLINE,
                'isMatrixFormAvailable'    => true,
                'expected'                 => [
                    'matrixFormType'              => Configuration::MATRIX_FORM_INLINE,
                    'isMatrixFormAvailable'       => true,
                    'isSimpleFormAvailable'       => false,
                    'isInlineMatrixFormAvailable' => true,
                    'isPopupMatrixFormAvailable'  => false
                ]
            ],
            'mobile, config none, matrix not available'    => [
                'isMobile'                 => true,
                'configuredMatrixFormType' => Configuration::MATRIX_FORM_NONE,
                'isMatrixFormAvailable'    => false,
                'expected'                 => [
                    'matrixFormType'              => Configuration::MATRIX_FORM_NONE,
                    'isMatrixFormAvailable'       => false,
                    'isSimpleFormAvailable'       => true,
                    'isInlineMatrixFormAvailable' => false,
                    'isPopupMatrixFormAvailable'  => false
                ]
            ],
            'mobile, config none, matrix available'        => [
                'isMobile'                 => true,
                'configuredMatrixFormType' => Configuration::MATRIX_FORM_NONE,
                'isMatrixFormAvailable'    => true,
                'expected'                 => [
                    'matrixFormType'              => Configuration::MATRIX_FORM_NONE,
                    'isMatrixFormAvailable'       => false,
                    'isSimpleFormAvailable'       => true,
                    'isInlineMatrixFormAvailable' => false,
                    'isPopupMatrixFormAvailable'  => false
                ]
            ],
            'mobile, config popup, matrix not available'   => [
                'isMobile'                 => true,
                'configuredMatrixFormType' => Configuration::MATRIX_FORM_POPUP,
                'isMatrixFormAvailable'    => false,
                'expected'                 => [
                    'matrixFormType'              => Configuration::MATRIX_FORM_NONE,
                    'isMatrixFormAvailable'       => false,
                    'isSimpleFormAvailable'       => true,
                    'isInlineMatrixFormAvailable' => false,
                    'isPopupMatrixFormAvailable'  => false
                ]
            ],
            'mobile, config popup, matrix available'       => [
                'isMobile'                 => true,
                'configuredMatrixFormType' => Configuration::MATRIX_FORM_POPUP,
                'isMatrixFormAvailable'    => true,
                'expected'                 => [
                    'matrixFormType'              => Configuration::MATRIX_FORM_POPUP,
                    'isMatrixFormAvailable'       => true,
                    'isSimpleFormAvailable'       => false,
                    'isInlineMatrixFormAvailable' => false,
                    'isPopupMatrixFormAvailable'  => true
                ]
            ],
            'mobile, config inline, matrix not available'  => [
                'isMobile'                 => true,
                'configuredMatrixFormType' => Configuration::MATRIX_FORM_INLINE,
                'isMatrixFormAvailable'    => false,
                'expected'                 => [
                    'matrixFormType'              => Configuration::MATRIX_FORM_NONE,
                    'isMatrixFormAvailable'       => false,
                    'isSimpleFormAvailable'       => true,
                    'isInlineMatrixFormAvailable' => false,
                    'isPopupMatrixFormAvailable'  => false
                ]
            ],
            'mobile, config inline, matrix available'      => [
                'isMobile'                 => true,
                'configuredMatrixFormType' => Configuration::MATRIX_FORM_INLINE,
                'isMatrixFormAvailable'    => true,
                'expected'                 => [
                    'matrixFormType'              => Configuration::MATRIX_FORM_POPUP,
                    'isMatrixFormAvailable'       => true,
                    'isSimpleFormAvailable'       => false,
                    'isInlineMatrixFormAvailable' => false,
                    'isPopupMatrixFormAvailable'  => true
                ]
            ],
        ];
    }

    /**
     * @dataProvider getAvailableMatrixFormTypeForProductViewDataProvider
     */
    public function testIsSimpleFormAvailableForProductView(
        ?string $matrixFormType,
        array $expected
    ): void {
        $product = $this->setExpectationsForGetAvailableMatrixFormTypeForProductView($matrixFormType);

        self::assertSame(
            $expected['isSimpleFormAvailable'],
            $this->provider->isSimpleFormAvailable($product)
        );
    }

    /**
     * @dataProvider getAvailableMatrixFormTypeForProductViewDataProvider
     */
    public function testIsInlineMatrixFormAvailableForProductView(
        ?string $matrixFormType,
        array $expected
    ): void {
        $product = $this->setExpectationsForGetAvailableMatrixFormTypeForProductView($matrixFormType);

        self::assertSame(
            $expected['isInlineMatrixFormAvailable'],
            $this->provider->isInlineMatrixFormAvailable($product)
        );
    }

    /**
     * @dataProvider getAvailableMatrixFormTypeForProductViewDataProvider
     */
    public function testIsPopupMatrixFormAvailableForProductView(
        ?string $matrixFormType,
        array $expected
    ): void {
        $product = $this->setExpectationsForGetAvailableMatrixFormTypeForProductView($matrixFormType);

        self::assertSame(
            $expected['isPopupMatrixFormAvailable'],
            $this->provider->isPopupMatrixFormAvailable($product)
        );
    }

    /**
     * @dataProvider getAvailableMatrixFormTypeForProductViewDataProvider
     */
    public function testIsMatrixFormAvailableForProductView(
        ?string $matrixFormType,
        array $expected
    ): void {
        $product = $this->setExpectationsForGetAvailableMatrixFormTypeForProductView($matrixFormType);

        self::assertSame(
            $expected['isMatrixFormAvailable'],
            $this->provider->isMatrixFormAvailable($product)
        );
    }

    /**
     * @dataProvider getAvailableMatrixFormTypeForProductViewDataProvider
     */
    public function testGetAvailableMatrixFormTypeForProductView(
        ?string $matrixFormType,
        array $expected
    ): void {
        $product = $this->setExpectationsForGetAvailableMatrixFormTypeForProductView($matrixFormType);

        self::assertSame(
            $expected['matrixFormType'],
            $this->provider->getAvailableMatrixFormType($product)
        );
    }

    public function getAvailableMatrixFormTypeForProductViewDataProvider(): array
    {
        return [
            'matrix unknown' => [
                'matrixFormType' => null,
                'expected'       => [
                    'matrixFormType'              => Configuration::MATRIX_FORM_NONE,
                    'isMatrixFormAvailable'       => false,
                    'isSimpleFormAvailable'       => true,
                    'isInlineMatrixFormAvailable' => false,
                    'isPopupMatrixFormAvailable'  => false
                ]
            ],
            'matrix none'    => [
                'matrixFormType' => Configuration::MATRIX_FORM_NONE,
                'expected'       => [
                    'matrixFormType'              => Configuration::MATRIX_FORM_NONE,
                    'isMatrixFormAvailable'       => false,
                    'isSimpleFormAvailable'       => true,
                    'isInlineMatrixFormAvailable' => false,
                    'isPopupMatrixFormAvailable'  => false
                ]
            ],
            'matrix inline'  => [
                'matrixFormType' => Configuration::MATRIX_FORM_INLINE,
                'expected'       => [
                    'matrixFormType'              => Configuration::MATRIX_FORM_INLINE,
                    'isMatrixFormAvailable'       => true,
                    'isSimpleFormAvailable'       => false,
                    'isInlineMatrixFormAvailable' => true,
                    'isPopupMatrixFormAvailable'  => false
                ]
            ],
            'matrix popup'   => [
                'matrixFormType' => Configuration::MATRIX_FORM_POPUP,
                'expected'       => [
                    'matrixFormType'              => Configuration::MATRIX_FORM_POPUP,
                    'isMatrixFormAvailable'       => true,
                    'isSimpleFormAvailable'       => false,
                    'isInlineMatrixFormAvailable' => false,
                    'isPopupMatrixFormAvailable'  => true
                ]
            ],
        ];
    }

    public function testGetAvailableMatrixFormTypeForProductViewForNotConfigurableProduct(): void
    {
        $product = new ProductView();
        $product->set('id', 123);
        $product->set('type', Product::TYPE_SIMPLE);

        $this->productFormAvailabilityProvider->expects(self::never())
            ->method('getAvailableMatrixFormTypes');

        self::assertSame(
            Configuration::MATRIX_FORM_NONE,
            $this->provider->getAvailableMatrixFormType($product)
        );
    }
}
