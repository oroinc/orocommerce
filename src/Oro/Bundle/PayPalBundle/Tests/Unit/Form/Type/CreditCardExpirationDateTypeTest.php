<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\PayPalBundle\Form\Type\CreditCardExpirationDateType;
use Oro\Bundle\PayPalBundle\Form\Type\CreditCardType;

class CreditCardExpirationDateTypeTest extends FormIntegrationTestCase
{
    /**
     * @var CreditCardType
     */
    protected $formType;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new CreditCardExpirationDateType();
    }

    /**
     * @dataProvider formConfigurationProvider
     * @param array $formFields
     * @param array $formOptions
     */
    public function testFormConfiguration(array $formFields, array $formOptions)
    {
        $form = $this->factory->create($this->formType);
        $this->assertFormOptions($form->getConfig(), $formOptions);
        foreach ($formFields as $fieldname => $fieldData) {
            $this->assertTrue($form->has($fieldname));
            $field = $form->get($fieldname);
            $this->assertEquals($field->getConfig()->getType()->getName(), $fieldData['type']);
            $this->assertFormOptions($field->getConfig(), $fieldData['options']);
        }
    }

    /**
     * @return array
     */
    public function formConfigurationProvider()
    {
        return [
            [
                [
                    'month' => [
                        'type' => 'choice',
                        'options' => [
                            'required' => true,
                        ],
                    ],
                    'year' => [
                        'type' => 'choice',
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
                    'years' => range(date('y'), date('y') + CreditCardExpirationDateType::YEAR_PERIOD),
                    'months' => ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12']
                ]
            ],
        ];
    }

    /**
     * @param FormConfigInterface $formConfig
     * @param array $formOptions
     */
    public function assertFormOptions(FormConfigInterface $formConfig, $formOptions)
    {
        $options = $formConfig->getOptions();
        foreach ($formOptions as $formOptionName => $formOptionData) {
            $this->assertTrue($formConfig->hasOption($formOptionName));
            $this->assertEquals($formOptionData, $options[$formOptionName]);
        }
    }

    public function testGetName()
    {
        $this->assertEquals(CreditCardExpirationDateType::NAME, $this->formType->getName());
    }
}
