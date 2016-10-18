<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Intl\Locale\Locale;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\PricingBundle\Form\Type\EnabledCurrencySelectionType;

class EnabledCurrencySelectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var CurrencySelectionType
     */
    protected $formType;

    /**
     * @var \Oro\Bundle\ConfigBundle\Config\ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var \Oro\Bundle\LocaleBundle\Model\LocaleSettings|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $localeSettings;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper
     */
    protected $currencyNameHelper;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\CurrencyBundle\Config\CurrencyConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->localeSettings = $this
            ->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->setMethods(['getCurrency', 'getLocale'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->localeSettings->expects($this->any())
            ->method('getLocale')
            ->willReturn(Locale::getDefault());

        $this->currencyNameHelper = $this
            ->getMockBuilder('Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new EnabledCurrencySelectionType(
            $this->configManager,
            $this->localeSettings,
            $this->currencyNameHelper
        );
    }

    public function testGetName()
    {
        $this->assertEquals(EnabledCurrencySelectionType::NAME, $this->formType->getName());
    }

    public function testGetCurrencySelectorConfigKey()
    {
        $this->assertEquals(
            EnabledCurrencySelectionType::CURRENCY_SELECTOR_CONFIG_KEY,
            $this->formType->getCurrencySelectorConfigKey()
        );
    }
}
