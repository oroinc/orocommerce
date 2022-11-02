<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Provider\ProductFormAvailabilityProvider;
use Oro\Bundle\ProductBundle\Provider\ProductMatrixAvailabilityProvider;
use Oro\Bundle\UIBundle\Provider\UserAgent;
use Oro\Bundle\UIBundle\Provider\UserAgentProvider;

class ProductFormAvailabilityProviderTest extends \PHPUnit\Framework\TestCase
{
    private const MATRIX_FORM_CONFIG_OPTION_NAME = 'test.matrix_form_type';

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ProductMatrixAvailabilityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $productMatrixAvailabilityProvider;

    /** @var UserAgentProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $userAgentProvider;

    /** @var ProductFormAvailabilityProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->productMatrixAvailabilityProvider = $this->createMock(ProductMatrixAvailabilityProvider::class);
        $this->userAgentProvider = $this->createMock(UserAgentProvider::class);

        $this->provider = new ProductFormAvailabilityProvider(
            $this->configManager,
            self::MATRIX_FORM_CONFIG_OPTION_NAME,
            $this->productMatrixAvailabilityProvider,
            $this->userAgentProvider
        );
    }

    public function testGetAvailableMatrixFormTypesWhenConfigurableProductDataAreEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The configurable product data must not be empty.');

        $configurableProductData = [];

        $this->configManager->expects(self::never())
            ->method('get');
        $this->productMatrixAvailabilityProvider->expects(self::never())
            ->method('getMatrixAvailabilityByConfigurableProductData');
        $this->userAgentProvider->expects(self::never())
            ->method('getUserAgent');

        $this->provider->getAvailableMatrixFormTypes($configurableProductData);
    }

    public function testGetAvailableMatrixFormTypesWhenMatrixFormsAreDisabledInConfig(): void
    {
        $configurableProductData = [
            123 => ['each', 2]
        ];

        $this->configManager->expects(self::once())
            ->method('get')
            ->with(self::MATRIX_FORM_CONFIG_OPTION_NAME)
            ->willReturn(Configuration::MATRIX_FORM_NONE);

        $this->productMatrixAvailabilityProvider->expects(self::never())
            ->method('getMatrixAvailabilityByConfigurableProductData');

        $this->userAgentProvider->expects(self::never())
            ->method('getUserAgent');

        self::assertSame([], $this->provider->getAvailableMatrixFormTypes($configurableProductData));
    }

    public function testGetAvailableMatrixFormTypesWhenMatrixFormAvailabilityIsNotReturnedForProduct(): void
    {
        $configurableProductData = [
            123 => ['each', 2]
        ];
        $matrixAvailability = [];

        $this->configManager->expects(self::once())
            ->method('get')
            ->with(self::MATRIX_FORM_CONFIG_OPTION_NAME)
            ->willReturn(Configuration::MATRIX_FORM_INLINE);

        $this->productMatrixAvailabilityProvider->expects(self::once())
            ->method('getMatrixAvailabilityByConfigurableProductData')
            ->with($configurableProductData)
            ->willReturn($matrixAvailability);

        $this->userAgentProvider->expects(self::never())
            ->method('getUserAgent');

        self::assertSame([], $this->provider->getAvailableMatrixFormTypes($configurableProductData));
    }

    public function testGetAvailableMatrixFormTypesWhenMatrixFormIsNotAvailableForProduct(): void
    {
        $configurableProductData = [
            123 => ['each', 2]
        ];
        $matrixAvailability = [
            123 => false
        ];

        $this->configManager->expects(self::once())
            ->method('get')
            ->with(self::MATRIX_FORM_CONFIG_OPTION_NAME)
            ->willReturn(Configuration::MATRIX_FORM_INLINE);

        $this->productMatrixAvailabilityProvider->expects(self::once())
            ->method('getMatrixAvailabilityByConfigurableProductData')
            ->with($configurableProductData)
            ->willReturn($matrixAvailability);

        $this->userAgentProvider->expects(self::never())
            ->method('getUserAgent');

        self::assertSame([], $this->provider->getAvailableMatrixFormTypes($configurableProductData));
    }

    public function testGetAvailableMatrixFormTypesWhenMatrixFormIsAvailableForProduct(): void
    {
        $configurableProductData = [
            123 => ['each', 2]
        ];
        $matrixAvailability = [
            123 => true
        ];

        $this->configManager->expects(self::once())
            ->method('get')
            ->with(self::MATRIX_FORM_CONFIG_OPTION_NAME)
            ->willReturn(Configuration::MATRIX_FORM_INLINE);

        $this->productMatrixAvailabilityProvider->expects(self::once())
            ->method('getMatrixAvailabilityByConfigurableProductData')
            ->with($configurableProductData)
            ->willReturn($matrixAvailability);

        $userAgent = $this->createMock(UserAgent::class);
        $this->userAgentProvider->expects(self::once())
            ->method('getUserAgent')
            ->willReturn($userAgent);
        $userAgent->expects(self::once())
            ->method('isMobile')
            ->willReturn(false);

        self::assertSame(
            [
                123 => Configuration::MATRIX_FORM_INLINE
            ],
            $this->provider->getAvailableMatrixFormTypes($configurableProductData)
        );
    }

    public function testGetAvailableMatrixFormTypesWhenMatrixFormIsAvailableForProductAndMobileView(): void
    {
        $configurableProductData = [
            123 => ['each', 2]
        ];
        $matrixAvailability = [
            123 => true
        ];

        $this->configManager->expects(self::once())
            ->method('get')
            ->with(self::MATRIX_FORM_CONFIG_OPTION_NAME)
            ->willReturn(Configuration::MATRIX_FORM_INLINE);

        $this->productMatrixAvailabilityProvider->expects(self::once())
            ->method('getMatrixAvailabilityByConfigurableProductData')
            ->with($configurableProductData)
            ->willReturn($matrixAvailability);

        $userAgent = $this->createMock(UserAgent::class);
        $this->userAgentProvider->expects(self::once())
            ->method('getUserAgent')
            ->willReturn($userAgent);
        $userAgent->expects(self::once())
            ->method('isMobile')
            ->willReturn(true);

        self::assertSame(
            [
                123 => Configuration::MATRIX_FORM_POPUP
            ],
            $this->provider->getAvailableMatrixFormTypes($configurableProductData)
        );
    }

    public function testGetAvailableMatrixFormTypesWhenMatrixFormIsAvailableForProductAndGalleryView(): void
    {
        $configurableProductData = [
            123 => ['each', 2]
        ];
        $matrixAvailability = [
            123 => true
        ];

        $this->configManager->expects(self::once())
            ->method('get')
            ->with(self::MATRIX_FORM_CONFIG_OPTION_NAME)
            ->willReturn(Configuration::MATRIX_FORM_INLINE);

        $this->productMatrixAvailabilityProvider->expects(self::once())
            ->method('getMatrixAvailabilityByConfigurableProductData')
            ->with($configurableProductData)
            ->willReturn($matrixAvailability);

        $this->userAgentProvider->expects(self::never())
            ->method('getUserAgent');

        self::assertSame(
            [
                123 => Configuration::MATRIX_FORM_POPUP
            ],
            $this->provider->getAvailableMatrixFormTypes($configurableProductData, 'gallery-view')
        );
    }
}
