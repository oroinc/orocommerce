<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validation;

use OroB2B\Bundle\PaymentBundle\Form\Type\CreditCardExpirationDateType;
use OroB2B\Bundle\ValidationBundle\Validator\Constraints\Integer;
use OroB2B\Bundle\PaymentBundle\Form\Type\CreditCardType;

class CreditCardTypeTest extends FormIntegrationTestCase
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

        $this->formType = new CreditCardType();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    CreditCardExpirationDateType::NAME => new CreditCardExpirationDateType(),
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * @dataProvider formConfigurationProvider
     * @param array $formFields
     */
    public function testFormConfiguration(array $formFields)
    {
        $form = $this->factory->create($this->formType);
        foreach ($formFields as $fieldname => $fieldData) {
            $this->assertTrue($form->has($fieldname));
            $field = $form->get($fieldname);
            $this->assertEquals($field->getConfig()->getType()->getName(), $fieldData['type']);
            foreach ($fieldData['options'] as $dataKey => $dataValue) {
                $this->assertTrue($field->getConfig()->hasOption($dataKey));
                $options = $field->getConfig()->getOptions();
                $this->assertEquals($dataValue, $options[$dataKey]);
            }
        }
        $this->assertEquals('orob2b.payment.methods.credit_card.label', $form->getConfig()->getOptions()['label']);
    }

    /**
     * @return array
     */
    public function formConfigurationProvider()
    {
        return [
            [
                [
                    'ACCT' => [
                        'type' => 'text',
                        'options' => [
                            'required' => true,
                            'label' => 'orob2b.payment.credit_card.card_number.label',
                            'mapped' => false,
                            'attr' => [
                                'data-validation' => [
                                    'credit-card-number' => [
                                        'message' => 'orob2b.payment.validation.credit_card',
                                        'payload' => null,
                                    ],
                                    'credit-card-type' => [
                                        'message' => 'orob2b.payment.validation.credit_card_type',
                                        'payload' => null,
                                    ],
                                ],
                                'data-credit-card-type-validator' => 'credit-card-type',
                                'data-gateway' => true,
                                'data-card-number' => true,
                                'autocomplete' => 'off',
                            ],
                            'constraints' => [
                                new Integer(),
                                new NotBlank(),
                                new Length(['min' => '12', 'max' => '19'])
                            ],
                        ],
                    ],
                    'expirationDate' => [
                        'type' => 'orob2b_payment_credit_card_expiration_date',
                        'options' => [
                            'required' => true,
                            'label' => 'orob2b.payment.credit_card.expiration_date.label',
                            'mapped' => false,
                            'placeholder' => [
                                'year' => 'Year',
                                'month' => 'Month',
                                'day' => ''
                            ],
                            'attr' => [
                                'data-expiration-date' => true
                            ]
                        ],
                    ],
                    'EXPDATE' => [
                        'type' => 'hidden',
                        'options' => [],
                    ],
                    'CVV2' => [
                        'type' => 'password',
                        'options' => [
                            'required' => true,
                            'label' => 'orob2b.payment.credit_card.cvv2.label',
                            'mapped' => false,
                            'block_name' => 'payment_credit_card_cvv',
                            'constraints' => [
                                new Integer(['message' => 'orob2b.payment.number.error']),
                                new NotBlank(),
                                new Length(['min' => 3, 'max' => 4]),
                            ],
                            'attr' => [
                                'data-card-cvv' => true,
                                'data-gateway' => true,
                            ]
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(CreditCardType::NAME, $this->formType->getName());
    }

    public function testFinishView()
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $formView = new FormView();
        $formChildrenView = new FormView();
        $formChildrenView->vars = [
            'full_name' => 'full_name',
            'name' => 'name'
        ];
        $formView->children = [$formChildrenView];

        $this->formType->finishView($formView, $form, []);

        foreach ($formView->children as $formItemData) {
            $this->assertEquals('name', $formItemData->vars['full_name']);
        }
    }
}
