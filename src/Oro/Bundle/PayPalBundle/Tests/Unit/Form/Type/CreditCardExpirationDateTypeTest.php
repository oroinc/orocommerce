<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Form\Type;

use Oro\Bundle\PayPalBundle\Form\Type\CreditCardExpirationDateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class CreditCardExpirationDateTypeTest extends FormIntegrationTestCase
{
    private const YEAR_PERIOD = 10;

    /**
     * @dataProvider formConfigurationProvider
     */
    public function testFormConfiguration(array $formFields, array $formOptions)
    {
        $form = $this->factory->create(CreditCardExpirationDateType::class);
        $this->assertFormOptions($form->getConfig(), $formOptions);
        foreach ($formFields as $fieldname => $fieldData) {
            $this->assertTrue($form->has($fieldname));
            $field = $form->get($fieldname);
            $this->assertInstanceOf($fieldData['type'], $field->getConfig()->getType()->getInnerType());
            $this->assertFormOptions($field->getConfig(), $fieldData['options']);
        }
    }

    public function formConfigurationProvider(): array
    {
        return [
            [
                [
                    'month' => [
                        'type' => ChoiceType::class,
                        'options' => [
                            'required' => true,
                        ],
                    ],
                    'year' => [
                        'type' => ChoiceType::class,
                        'options' => [
                            'required' => true,
                        ],
                    ],
                ],
                [
                    'model_timezone' => 'UTC',
                    'view_timezone' => 'UTC',
                    'format' => 'dMy',
                    'input' => 'array',
                    'years' => range(date('y'), date('y') + self::YEAR_PERIOD),
                    'months' => ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12']
                ]
            ],
        ];
    }

    private function assertFormOptions(FormConfigInterface $formConfig, array $formOptions): void
    {
        $options = $formConfig->getOptions();
        foreach ($formOptions as $formOptionName => $formOptionData) {
            $this->assertTrue($formConfig->hasOption($formOptionName));
            $this->assertEquals($formOptionData, $options[$formOptionName]);
        }
    }
}
