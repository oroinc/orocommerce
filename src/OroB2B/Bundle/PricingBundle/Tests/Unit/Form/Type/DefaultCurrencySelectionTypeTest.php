<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Translation\Translator;

use OroB2B\Bundle\PricingBundle\Form\Type\DefaultCurrencySelectionType;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class DefaultCurrencySelectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var LocaleSettings
     */
    protected $localeSettings;

    /**
     * @var Translator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->translator = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    'orob2b_pricing_default_currency_selection' => new DefaultCurrencySelectionType(
                        $this->configManager,
                        $this->localeSettings,
                        $this->translator
                    ),
                ],
                []
            ),
        ];
    }

    /**
     * @dataProvider submitFormDataProvider
     * @param array $enableCurrencies
     * @param string $defaultCurrency
     * @param bool $isEnableCurrenciesDefault
     * @param bool $isDefaultCurrencyDefault
     * @param bool $isValid
     */
    public function testSubmitForm(
        array $enableCurrencies,
        $defaultCurrency,
        $isEnableCurrenciesDefault,
        $isDefaultCurrencyDefault,
        $isValid
    ) {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_currency.allowed_currencies')
            ->willReturn(['USD', 'CAD', 'EUR']);

        $form = $this->factory->create('orob2b_pricing_default_currency_selection', null, ['multiple' => true]);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $parentForm */
        $rootForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $rootForm->expects($this->any())
            ->method('getRoot')
            ->willReturn($rootForm);
        $rootForm->expects($this->once())
            ->method('getName')
            ->willReturn('pricing');
        $rootForm->expects($this->any())
            ->method('has')
            ->with(DefaultCurrencySelectionType::ENABLED_CURRENCIES_NAME)
            ->willReturn(true);

        $defaultCurrenciesForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $defaultCurrenciesForm->expects($this->any())
            ->method('getData')
            ->willReturn([
                'use_parent_scope_value' => $isDefaultCurrencyDefault,
                'value' => $defaultCurrency
            ]);

        $enabledCurrenciesForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $enabledCurrenciesForm->expects($this->any())
            ->method('getData')
            ->willReturn([
                'use_parent_scope_value' => $isEnableCurrenciesDefault,
                'value' => $enableCurrencies
            ]);

        $rootForm->expects($this->any())
            ->method('get')
            ->willReturnMap([
                [DefaultCurrencySelectionType::DEFAULT_CURRENCY_NAME, $defaultCurrenciesForm],
                [DefaultCurrencySelectionType::ENABLED_CURRENCIES_NAME, $enabledCurrenciesForm]
            ]);


        $form->setParent($rootForm);

        $form->submit(['USD']);
        $this->assertSame($isValid, $form->isValid());
    }

    /**
     * @return array
     */
    public function submitFormDataProvider()
    {
        return [
            'valid without default' => [
                'enableCurrencies' => ['USD', 'CAD', 'EUR'],
                'defaultCurrency' => 'USD',
                'isEnableCurrenciesDefault' => false,
                'isDefaultCurrencyDefault' => false,
                'isValid' => true
            ],
            'invalid without default' => [
                'enableCurrencies' => ['CAD'],
                'defaultCurrency' => 'EUR',
                'isEnableCurrenciesDefault' => false,
                'isDefaultCurrencyDefault' => false,
                'isValid' => false
            ],
            'valid with defaultCurrency default' => [
                'enableCurrencies' => ['CAD', 'USD'],
                'defaultCurrency' => 'USD',
                'isEnableCurrenciesDefault' => false,
                'isDefaultCurrencyDefault' => true,
                'isValid' => true
            ],
            'invalid with defaultCurrency default' => [
                'enableCurrencies' => ['CAD', 'EUR'],
                'defaultCurrency' => '',
                'isEnableCurrenciesDefault' => false,
                'isDefaultCurrencyDefault' => true,
                'isValid' => false
            ],
            'valid with enableCurrencies default' => [
                'enableCurrencies' => [],
                'defaultCurrency' => 'USD',
                'isEnableCurrenciesDefault' => true,
                'isDefaultCurrencyDefault' => false,
                'isValid' => true
            ],
            'valid with default' => [
                'enableCurrencies' => [],
                'defaultCurrency' => 'USD',
                'isEnableCurrenciesDefault' => true,
                'isDefaultCurrencyDefault' => true,
                'isValid' => true
            ],
        ];
    }

    public function testGetName()
    {
        $formType = new DefaultCurrencySelectionType(
            $this->configManager,
            $this->localeSettings,
            $this->translator
        );
        $this->assertEquals(DefaultCurrencySelectionType::NAME, $formType->getName());
    }
}
