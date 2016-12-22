<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityExtendBundle\Validator\Validation;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRuleDestinationPostalCode;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodsConfigsRuleDestinationPostalCodeType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;

class PaymentMethodsConfigsRuleDestinationPostalCodeTypeTest extends FormIntegrationTestCase
{
    /**
     * @var PaymentMethodsConfigsRuleDestinationPostalCodeType
     */
    protected $formType;

    protected function setUp()
    {
        $this->formType = new PaymentMethodsConfigsRuleDestinationPostalCodeType();

        parent::setUp();
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(
            PaymentMethodsConfigsRuleDestinationPostalCodeType::NAME,
            $this->formType->getBlockPrefix()
        );
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array $data
     */
    public function testSubmit($data)
    {
        $form = $this->factory->create($this->formType, $data);

        $this->assertSame($data, $form->getData());

        $form->submit([
            'name' => 'code1',
        ]);

        $PaymentMethodsConfigsRuleDestinationPostalCode = (new PaymentMethodsConfigsRuleDestinationPostalCode())
            ->setName('code1')
        ;

        $this->assertTrue($form->isValid());
        $this->assertEquals($PaymentMethodsConfigsRuleDestinationPostalCode, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            [new PaymentMethodsConfigsRuleDestinationPostalCode()],
            [
                (new PaymentMethodsConfigsRuleDestinationPostalCode())->setName('code0')
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    CollectionType::NAME => new CollectionType(),
                    PaymentMethodsConfigsRuleDestinationPostalCodeType::NAME =>
                        new PaymentMethodsConfigsRuleDestinationPostalCodeType()
                    ,
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }
}
