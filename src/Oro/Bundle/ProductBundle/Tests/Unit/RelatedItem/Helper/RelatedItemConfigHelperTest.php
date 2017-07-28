<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\RelatedItem\Helper;

use Oro\Bundle\ProductBundle\RelatedItem\AbstractRelatedItemConfigProvider;
use Oro\Bundle\ProductBundle\RelatedItem\Helper\RelatedItemConfigHelper;

class RelatedItemConfigHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var RelatedItemConfigHelper */
    private $helper;

    protected function setUp()
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

    public function testReturnsNullOnNonExistingProvider()
    {
        /** @var AbstractRelatedItemConfigProvider $provider */
        $provider = $this->createMock(AbstractRelatedItemConfigProvider::class);
        $this->helper->addConfigProvider('related_product', $provider);

        $this->assertNull($this->helper->getConfigProvider('non_existing'));
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

    /**
     * @param $isEnabled
     * @return AbstractRelatedItemConfigProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getProviderMock($isEnabled)
    {
        /** @var AbstractRelatedItemConfigProvider|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->createMock(AbstractRelatedItemConfigProvider::class);
        $provider->expects($this->any())->method('isEnabled')->willReturn($isEnabled);

        return $provider;
    }
}
