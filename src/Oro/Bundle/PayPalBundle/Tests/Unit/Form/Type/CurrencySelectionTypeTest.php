<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\CurrencyBundle\Tests\Unit\Utils\CurrencyNameHelperStub;
use Oro\Bundle\PayPalBundle\Form\Type\CurrencySelectionType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class CurrencySelectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var CurrencySelectionType
     */
    protected $formType;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CurrencyProviderInterface
     */
    protected $currencyProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Oro\Bundle\LocaleBundle\Model\LocaleSettings
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

        $this->currencyProvider = $this->getMockBuilder(CurrencyProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->localeSettings = $this
            ->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->setMethods(['getCurrency', 'getLocale'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->localeSettings->expects($this->any())
            ->method('getLocale')
            ->willReturn(\Locale::getDefault());

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper */
        $this->currencyNameHelper = new CurrencyNameHelperStub();

        $this->formType = new CurrencySelectionType(
            $this->currencyProvider,
            $this->localeSettings,
            $this->currencyNameHelper
        );
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array $allowedCurrencies
     * @param array $inputOptions
     * @param array $expectedOptions
     * @param string $submittedData
     */
    public function testSubmit(
        array $allowedCurrencies,
        array $inputOptions,
        array $expectedOptions,
        $submittedData
    ) {
        $hasCustomCurrencies = isset($inputOptions['currencies_list']) || !empty($inputOptions['full_currency_list']);
        $this->currencyProvider->expects($hasCustomCurrencies ? $this->never() : $this->once())
            ->method('getCurrencyList')
            ->willReturn($allowedCurrencies);

        $form = $this->factory->create($this->formType, null, $inputOptions);

        $formConfig = $form->getConfig();
        foreach ($expectedOptions as $key => $value) {
            $this->assertTrue($formConfig->hasOption($key));
        }
        $this->assertEquals($expectedOptions['choices'], $form->createView()->vars['choices']);

        $this->assertEquals($form->getData(), '');
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($submittedData, $form->getData());
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function submitDataProvider()
    {
        return [
            'full currency name and data from system config' => [
                'allowedCurrencies' => ['USD'],
                'inputOptions' => [],
                'expectedOptions' => [
                    'compact' => false,
                    'choices' => [
                        new ChoiceView('USD', 'USD', 'USD-full_name')
                    ]
                ],
                'submittedData' => 'USD'
            ],
            'compact currency name and data from system config' => [
                'allowedCurrencies' => ['USD'],
                'inputOptions' => [
                    'compact' => true
                ],
                'expectedOptions' => [
                    'compact' => true,
                    'choices' => [
                        new ChoiceView('USD', 'USD', 'USD-iso_code')
                    ]
                ],
                'submittedData' => 'USD'
            ],
            'full currency name and data from locale settings' => [
                'allowedCurrencies' => ['EUR'],
                'inputOptions' => [
                    'compact' => false,
                    'currencies_list' => null
                ],
                'expectedOptions' => [
                    'compact' => false,
                    'choices' => [
                        new ChoiceView('EUR', 'EUR', 'EUR-full_name')
                    ]
                ],
                'submittedData' => 'EUR'
            ],
            'full currency name and data from currencies_list option' => [
                'allowedCurrencies' => ['USD'],
                'inputOptions' => [
                    'compact' => false,
                    'currencies_list' => ['RUB']
                ],
                'expectedOptions' => [
                    'compact' => false,
                    'choices' => [
                        new ChoiceView('RUB', 'RUB', 'RUB-full_name'),
                    ]
                ],
                'submittedData' => 'RUB'
            ],
            'full currency name, data from system config and additional currencies' => [
                'allowedCurrencies' => ['USD'],
                'inputOptions' => [
                    'additional_currencies' => ['GBP']
                ],
                'expectedOptions' => [
                    'compact' => false,
                    'choices' => [
                        new ChoiceView('USD', 'USD', 'USD-full_name'),
                        new ChoiceView('GBP', 'GBP', 'GBP-full_name')
                    ]
                ],
                'submittedData' => 'GBP'
            ],
            'compact currency name, data from currencies_list option and additional currencies' => [
                'allowedCurrencies' => ['USD'],
                'inputOptions' => [
                    'compact' => true,
                    'currencies_list' => ['RUB'],
                    'additional_currencies' => ['GBP']
                ],
                'expectedOptions' => [
                    'compact' => true,
                    'choices' => [
                        new ChoiceView('RUB', 'RUB', 'RUB-iso_code'),
                        new ChoiceView('GBP', 'GBP', 'GBP-iso_code')
                    ]
                ],
                'submittedData' => 'GBP'
            ]
        ];
    }

    /**
     * @param array $legacyChoices
     *
     * @return array
     */
    protected function getChoiceViews(array $legacyChoices)
    {
        $choices = [];
        foreach ($legacyChoices as $key => $value) {
            $choices[] = new ChoiceView($key, $key, $value);
        }
        return $choices;
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\LogicException
     * @expectedExceptionMessage The option "currencies_list" must be null or not empty array.
     */
    public function testInvalidTypeOfCurrenciesListOption()
    {
        $this->factory->create($this->formType, null, ['currencies_list' => 'string']);
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\LogicException
     * @expectedExceptionMessage Found unknown currencies: CUR, TST.
     */
    public function testUnknownCurrency()
    {
        $this->factory->create($this->formType, null, ['currencies_list' => ['CUR', 'TST']]);
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\LogicException
     * @expectedExceptionMessage The option "additional_currencies" must be null or array.
     */
    public function testInvalidTypeOfAdditionalCurrenciesOption()
    {
        $this->factory->create($this->formType, null, ['additional_currencies' => 'string']);
    }

    public function testGetName()
    {
        $this->assertEquals(CurrencySelectionType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('choice', $this->formType->getParent());
    }
}
