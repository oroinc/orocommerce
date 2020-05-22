<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\RelatedItem\Helper;

use Oro\Bundle\ProductBundle\Exception\ConfigProviderNotFoundException;
use Oro\Bundle\ProductBundle\RelatedItem\AbstractRelatedItemConfigProvider;
use Oro\Bundle\ProductBundle\RelatedItem\Helper\RelatedItemConfigHelper;

class RelatedItemConfigHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var RelatedItemConfigHelper */
    private $helper;

    protected function setUp(): void
    {
        $this->helper = new RelatedItemConfigHelper();
    }

    public function testConfigProvidersCanBeAddedToHelper()
    {
        $this->assertCount(0, $this->helper->getConfigProviders());

        /** @var AbstractRelatedItemConfigProvider $provider */
        $provider = $this->createMock(AbstractRelatedItemConfigProvider::class);
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
        $providerEnabled = $this->getProviderMock(true);
        $providerDisabled = $this->getProviderMock(false);

        $this->helper->addConfigProvider('enabled', $providerEnabled);
        $this->helper->addConfigProvider('disabled', $providerDisabled);

        $this->assertTrue($this->helper->isAnyEnabled());
    }

    public function testIsAnyEnabledReturnsFalseIfNoneIsEnabled()
    {
        $providerDisabled = $this->getProviderMock(false);
        $providerDisabledTwo = $this->getProviderMock(false);

        $this->helper->addConfigProvider('disabled', $providerDisabled);
        $this->helper->addConfigProvider('disabled_2', $providerDisabledTwo);

        $this->assertFalse($this->helper->isAnyEnabled());
    }

    public function testGetRelatedItemsTranslationKeyReturnsDefaultKeyIfNoneConfigProviderIsEnabled()
    {
        $providerDisabled = $this->getProviderMock(false);
        $providerDisabledTwo = $this->getProviderMock(false);

        $this->helper->addConfigProvider('disabled', $providerDisabled);
        $this->helper->addConfigProvider('disabled_2', $providerDisabledTwo);

        $expected = RelatedItemConfigHelper::RELATED_ITEMS_TRANSLATION_NAMESPACE . '.';
        $expected .= RelatedItemConfigHelper::RELATED_ITEMS_TRANSLATION_DEFAULT;

        $this->assertEquals($this->helper->getRelatedItemsTranslationKey(), $expected);
    }

    public function testGetRelatedItemsTranslationKeyReturnsSpecificKeyIfOneConfigProviderIsEnabled()
    {
        $providerName = 'related_product';
        $providerEnabled = $this->getProviderMock(true);

        $this->helper->addConfigProvider($providerName, $providerEnabled);

        $expected = RelatedItemConfigHelper::RELATED_ITEMS_TRANSLATION_NAMESPACE . '.' . $providerName;

        $this->assertEquals($this->helper->getRelatedItemsTranslationKey(), $expected);
    }

    public function testGetRelatedItemsTranslationKeyReturnsReturnsDefaultIfMoreConfigProvidersAreEnabled()
    {
        $providerEnabled = $this->getProviderMock(true);
        $providerEnabledTwo = $this->getProviderMock(true);

        $this->helper->addConfigProvider('related_product', $providerEnabled);
        $this->helper->addConfigProvider('up_sell_product', $providerEnabledTwo);

        $expected = RelatedItemConfigHelper::RELATED_ITEMS_TRANSLATION_NAMESPACE . '.';
        $expected .= RelatedItemConfigHelper::RELATED_ITEMS_TRANSLATION_DEFAULT;

        $this->assertEquals($this->helper->getRelatedItemsTranslationKey(), $expected);
    }

    /**
     * @param $isEnabled
     * @return AbstractRelatedItemConfigProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getProviderMock($isEnabled)
    {
        /** @var AbstractRelatedItemConfigProvider|\PHPUnit\Framework\MockObject\MockObject $provider */
        $provider = $this->createMock(AbstractRelatedItemConfigProvider::class);
        $provider->expects($this->any())->method('isEnabled')->willReturn($isEnabled);

        return $provider;
    }
}
