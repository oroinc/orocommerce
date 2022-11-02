<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\RelatedItem\Helper;

use Oro\Bundle\ProductBundle\Exception\ConfigProviderNotFoundException;
use Oro\Bundle\ProductBundle\RelatedItem\Helper\RelatedItemConfigHelper;
use Oro\Bundle\ProductBundle\RelatedItem\RelatedItemConfigProviderInterface;

class RelatedItemConfigHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var RelatedItemConfigHelper */
    private $helper;

    protected function setUp(): void
    {
        $this->helper = new RelatedItemConfigHelper();
    }

    private function getProvider(bool $isEnabled): RelatedItemConfigProviderInterface
    {
        $provider = $this->createMock(RelatedItemConfigProviderInterface::class);
        $provider->expects($this->any())
            ->method('isEnabled')
            ->willReturn($isEnabled);

        return $provider;
    }

    public function testConfigProvidersCanBeAddedToHelper()
    {
        $this->assertCount(0, $this->helper->getConfigProviders());

        $provider = $this->createMock(RelatedItemConfigProviderInterface::class);
        $this->helper->addConfigProvider('related_product', $provider);

        $this->assertCount(1, $this->helper->getConfigProviders());
        $this->assertSame($provider, $this->helper->getConfigProvider('related_product'));
    }

    public function testReturnsConfigProviderNotFoundExceptionOnNonExistingProvider()
    {
        $this->expectException(ConfigProviderNotFoundException::class);

        $this->helper->getConfigProvider('non-existing');
    }

    public function testIsAnyEnabledReturnsTrueIfAtLeastOneIsEnabled()
    {
        $providerEnabled = $this->getProvider(true);
        $providerDisabled = $this->getProvider(false);

        $this->helper->addConfigProvider('enabled', $providerEnabled);
        $this->helper->addConfigProvider('disabled', $providerDisabled);

        $this->assertTrue($this->helper->isAnyEnabled());
    }

    public function testIsAnyEnabledReturnsFalseIfNoneIsEnabled()
    {
        $providerDisabled = $this->getProvider(false);
        $providerDisabledTwo = $this->getProvider(false);

        $this->helper->addConfigProvider('disabled', $providerDisabled);
        $this->helper->addConfigProvider('disabled_2', $providerDisabledTwo);

        $this->assertFalse($this->helper->isAnyEnabled());
    }

    public function testGetRelatedItemsTranslationKeyReturnsDefaultKeyIfNoneConfigProviderIsEnabled()
    {
        $providerDisabled = $this->getProvider(false);
        $providerDisabledTwo = $this->getProvider(false);

        $this->helper->addConfigProvider('disabled', $providerDisabled);
        $this->helper->addConfigProvider('disabled_2', $providerDisabledTwo);

        $this->assertEquals(
            'oro.product.sections.related_items',
            $this->helper->getRelatedItemsTranslationKey()
        );
    }

    public function testGetRelatedItemsTranslationKeyReturnsSpecificKeyIfOneConfigProviderIsEnabled()
    {
        $providerName = 'related_product';
        $providerEnabled = $this->getProvider(true);

        $this->helper->addConfigProvider($providerName, $providerEnabled);

        $this->assertEquals(
            'oro.product.sections.' . $providerName,
            $this->helper->getRelatedItemsTranslationKey()
        );
    }

    public function testGetRelatedItemsTranslationKeyReturnsReturnsDefaultIfMoreConfigProvidersAreEnabled()
    {
        $providerEnabled = $this->getProvider(true);
        $providerEnabledTwo = $this->getProvider(true);

        $this->helper->addConfigProvider('related_product', $providerEnabled);
        $this->helper->addConfigProvider('up_sell_product', $providerEnabledTwo);

        $this->assertEquals(
            'oro.product.sections.related_items',
            $this->helper->getRelatedItemsTranslationKey()
        );
    }
}
