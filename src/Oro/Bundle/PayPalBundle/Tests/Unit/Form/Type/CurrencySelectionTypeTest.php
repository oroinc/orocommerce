<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Intl\Intl;

use Oro\Bundle\PayPalBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Tests\Unit\Form\Type\CurrencySelectionTypeTest as CurrencyBundleCurrencySelectionTypeTest;

class CurrencySelectionTypeTest extends CurrencyBundleCurrencySelectionTypeTest
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

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

        $form = $this->doTestForm($inputOptions, $expectedOptions, $submittedData);

        $this->assertEquals($expectedOptions['choices'], $form->createView()->vars['choices']);
    }
    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function submitDataProvider()
    {
        $currencyBundle = Intl::getCurrencyBundle();
        $usdName = $currencyBundle->getCurrencyName('USD');
        $eurName = $currencyBundle->getCurrencyName('EUR');
        $gbpName = $currencyBundle->getCurrencyName('GBP');
        $rubName = $currencyBundle->getCurrencyName('RUB');

        return [
            'full currency name and data from system config' => [
                'allowedCurrencies' => ['USD'],
                'inputOptions' => [],
                'expectedOptions' => [
                    'compact' => false,
                    'choices' => [
                        new ChoiceView('USD', 'USD', $usdName)
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
                        new ChoiceView('USD', 'USD', 'USD')
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
                        new ChoiceView('EUR', 'EUR', $eurName)
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
                        new ChoiceView('RUB', 'RUB', $rubName),
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
                        new ChoiceView('USD', 'USD', $usdName),
                        new ChoiceView('GBP', 'GBP', $gbpName)
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
                        new ChoiceView('RUB', 'RUB', 'RUB'),
                        new ChoiceView('GBP', 'GBP', 'GBP')
                    ]
                ],
                'submittedData' => 'GBP'
            ]
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(CurrencySelectionType::NAME, $this->formType->getName());
    }
}
