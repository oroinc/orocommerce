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

    public function testIsInlineMatrixFormAvailableIsMobile()
    {
        $this->userAgent->expects($this->once())
            ->method('isMobile')
            ->willReturn(true);


        $this->assertFalse($this->provider->isInlineMatrixFormAvailable($this->getEntity(Product::class)));
    }

    public function testIsInlineMatrixFormAvailableConfigNotInline()
    {
        $this->userAgent->expects($this->once())
            ->method('isMobile')
            ->willReturn(false);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::MATRIX_FORM_ON_PRODUCT_LISTING))
            ->willReturn('none');

        $this->assertFalse($this->provider->isInlineMatrixFormAvailable($this->getEntity(Product::class)));
    }

    public function testIsInlineMatrixFormAvailableMatrixFormIsNotAvailable()
    {
        $this->userAgent->expects($this->once())
            ->method('isMobile')
            ->willReturn(false);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::MATRIX_FORM_ON_PRODUCT_LISTING))
            ->willReturn(Configuration::MATRIX_FORM_ON_PRODUCT_LISTING_INLINE);

        $product = $this->getEntity(Product::class);

        $this->productFormAvailabilityProvider->expects($this->once())
            ->method('isMatrixFormAvailable')
            ->with($product)
            ->willReturn(false);

        $this->assertFalse($this->provider->isInlineMatrixFormAvailable($product));
    }

    public function testIsInlineMatrixFormAvailable()
    {
        $this->userAgent->expects($this->once())
            ->method('isMobile')
            ->willReturn(false);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::MATRIX_FORM_ON_PRODUCT_LISTING))
            ->willReturn(Configuration::MATRIX_FORM_ON_PRODUCT_LISTING_INLINE);

        $product = $this->getEntity(Product::class);

        $this->productFormAvailabilityProvider->expects($this->once())
            ->method('isMatrixFormAvailable')
            ->with($product)
            ->willReturn(true);

        $this->assertTrue($this->provider->isInlineMatrixFormAvailable($product));
    }
}
