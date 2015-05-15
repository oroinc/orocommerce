<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;

class CurrencySelectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var CurrencySelectionType
     */
    protected $formType;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Oro\Bundle\ConfigBundle\Config\ConfigManager
     */
    protected $configManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Oro\Bundle\LocaleBundle\Model\LocaleSettings
     */
    protected $localeSettings;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager', ['get'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->localeSettings = $this
            ->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings', ['getCurrency', 'getLocale'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->localeSettings->expects($this->any())
            ->method('getLocale')
            ->willReturn('en');

        $this->formType = new CurrencySelectionType($this->configManager, $this->localeSettings);
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array $allowedCurrencies
     * @param string $localeCurrency
     * @param array $inputOptions
     * @param array $expectedOptions
     * @param string $submittedData
     */
    public function testSubmit(
        array $allowedCurrencies,
        $localeCurrency,
        array $inputOptions,
        array $expectedOptions,
        $submittedData
    ) {
        $this->configManager->expects(isset($inputOptions['currencies_list']) ? $this->never() : $this->once())
            ->method('get')
            ->with('oro_currency.allowed_currencies')
            ->willReturn($allowedCurrencies);

        $this->localeSettings->expects(count($allowedCurrencies) ? $this->never() : $this->once())
            ->method('getCurrency')
            ->willReturn($localeCurrency);

        $form = $this->factory->create($this->formType, null, $inputOptions);

        $formConfig = $form->getConfig();
        foreach ($expectedOptions as $key => $value) {
            $this->assertTrue($formConfig->hasOption($key));
            $this->assertEquals($value, $formConfig->getOption($key));
        }

        $this->assertNull($form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($submittedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'full currency name and data from system config' => [
                'allowedCurrencies' => ['USD', 'UAH'],
                'localeCurrency' => 'EUR',
                'inputOptions' => [],
                'expectedOptions' => [
                    'compact' => false,
                    'choices' => [
                        'USD' => 'US Dollar',
                        'UAH' => 'Ukrainian Hryvnia',
                    ]
                ],
                'submittedData' => 'UAH',
            ],
            'compact currency name and data from system config' => [
                'allowedCurrencies' => ['USD', 'UAH'],
                'localeCurrency' => 'EUR',
                'inputOptions' => [
                    'compact' => true,
                ],
                'expectedOptions' => [
                    'compact' => true,
                    'choices' => [
                        'USD' => 'USD',
                        'UAH' => 'UAH',
                    ]
                ],
                'submittedData' => 'UAH',
            ],
            'full currency name and data from locale settings' => [
                'allowedCurrencies' => [],
                'localeCurrency' => 'EUR',
                'inputOptions' => [
                    'compact' => false,
                    'currencies_list' => null,
                ],
                'expectedOptions' => [
                    'compact' => false,
                    'choices' => [
                        'EUR' => 'Euro',
                    ]
                ],
                'submittedData' => 'EUR',
            ],
            'full currency name and data from currencies_list option' => [
                'allowedCurrencies' => ['USD', 'UAH'],
                'localeCurrency' => 'EUR',
                'inputOptions' => [
                    'compact' => false,
                    'currencies_list' => ['RUB'],
                ],
                'expectedOptions' => [
                    'compact' => false,
                    'choices' => [
                        'RUB' => 'Russian Ruble',
                    ]
                ],
                'submittedData' => 'RUB',
            ]
        ];
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\LogicException
     * @expectedExceptionMessage The option "currencies_list" must be null or array.
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

    public function testGetName()
    {
        $this->assertEquals(CurrencySelectionType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('choice', $this->formType->getParent());
    }
}
