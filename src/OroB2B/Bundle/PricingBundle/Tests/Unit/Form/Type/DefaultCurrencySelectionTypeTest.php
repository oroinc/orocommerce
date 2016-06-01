<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Intl\Locale\Locale;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use OroB2B\Bundle\PricingBundle\Form\Type\DefaultCurrencySelectionType;

class DefaultCurrencySelectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var DefaultCurrencySelectionType
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
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->setMethods(['get'])
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

        $this->formType = new DefaultCurrencySelectionType($this->configManager, $this->localeSettings);
    }

    public function testGetName()
    {
        $this->assertEquals(DefaultCurrencySelectionType::NAME, $this->formType->getName());
    }
}
