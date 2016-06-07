<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\Translator;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PricingBundle\Form\Type\DefaultCurrencySelectionType;

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
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestStack;

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

        $this->requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');

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
                        $this->translator,
                        $this->requestStack
                    ),
                ],
                []
            ),
        ];
    }

    /**
     * @dataProvider submitFormDataProvider
     * @param array $defaultCurrency
     * @param array $enableCurrencies
     * @param string $submittedValue
     * @param bool $isValid
     */
    public function testSubmitForm(array $defaultCurrency, array $enableCurrencies, $submittedValue, $isValid)
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_currency.allowed_currencies')
            ->willReturn(['USD', 'CAD', 'EUR']);

        $currentRequest = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $currentRequest->expects($this->once())
            ->method('get')
            ->with('pricing')
            ->willReturn([
                DefaultCurrencySelectionType::DEFAULT_CURRENCY_NAME => $defaultCurrency,
                DefaultCurrencySelectionType::ENABLED_CURRENCIES_NAME => $enableCurrencies
            ]);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($currentRequest);

        $form = $this->factory->create('orob2b_pricing_default_currency_selection');

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $parentForm */
        $rootForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $rootForm->expects($this->once())
            ->method('getRoot')
            ->willReturn($rootForm);
        $rootForm->expects($this->once())
            ->method('getName')
            ->willReturn('pricing');
        $rootForm->expects($this->once())
            ->method('has')
            ->with(DefaultCurrencySelectionType::ENABLED_CURRENCIES_NAME)
            ->willReturn(true);

        $form->setParent($rootForm);

        $form->submit($submittedValue);
        $this->assertSame($isValid, $form->isValid());
    }

    /**
     * @return array
     */
    public function submitFormDataProvider()
    {
        return [
            'valid without default' => [
                'defaultCurrency' => [
                    'value' => 'USD'
                ],
                'enableCurrencies' => [
                    'value' => ['USD', 'CAD', 'EUR']
                ],
                'submittedValue' => 'USD',
                'isValid' => true
            ],
            'invalid without default' => [
                'defaultCurrency' => [
                    'value' => 'EUR'
                ],
                'enableCurrencies' => [
                    'value' => ['CAD']
                ],
                'submittedValue' => 'EUR',
                'isValid' => false
            ],
            'valid with defaultCurrency default' => [
                'defaultCurrency' => [
                    'use_parent_scope_value' => true,
                ],
                'enableCurrencies' => [
                    'value' => ['CAD', 'USD']
                ],
                'submittedValue' => '',
                'isValid' => true
            ],
            'invalid with defaultCurrency default' => [
                'defaultCurrency' => [
                    'use_parent_scope_value' => true,
                ],
                'enableCurrencies' => [
                    'value' => ['CAD', 'EUR']
                ],
                'submittedValue' => '',
                'isValid' => false
            ],
            'valid with enableCurrencies default' => [
                'defaultCurrency' => [
                    'value' => 'USD'
                ],
                'enableCurrencies' => [
                    'use_parent_scope_value' => true,
                    'value' => []
                ],
                'submittedValue' => 'USD',
                'isValid' => true
            ],
            'valid with default' => [
                'defaultCurrency' => [
                    'use_parent_scope_value' => true,
                ],
                'enableCurrencies' => [
                    'use_parent_scope_value' => true,
                ],
                'submittedValue' => '',
                'isValid' => true
            ]
        ];
    }

    public function testGetName()
    {
        $formType = new DefaultCurrencySelectionType(
            $this->configManager,
            $this->localeSettings,
            $this->translator,
            $this->requestStack
        );
        $this->assertEquals(DefaultCurrencySelectionType::NAME, $formType->getName());
    }
}
