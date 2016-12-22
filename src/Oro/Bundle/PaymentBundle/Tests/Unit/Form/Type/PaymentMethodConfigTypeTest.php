<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityExtendBundle\Validator\Validation;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodConfigType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;

class PaymentMethodConfigTypeTest extends FormIntegrationTestCase
{
    /**
     * @var PaymentMethodConfigType
     */
    protected $formType;

    protected function setUp()
    {
        $this->formType = new PaymentMethodConfigType();

        parent::setUp();
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(PaymentMethodConfigType::NAME, $this->formType->getBlockPrefix());
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
            'type' => 'MO',
            'options' => ['client_id' => 3],
        ]);

        $paymentMethodConfig = (new PaymentMethodConfig())
            ->setType('MO')
            ->setOptions(['client_id' => 3])
        ;

        $this->assertTrue($form->isValid());
        $this->assertEquals($paymentMethodConfig, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            [new PaymentMethodConfig()],
            [
                (new PaymentMethodConfig())->setType('PP')->setOptions(['client_id' => 5])
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
                    PaymentMethodConfigType::NAME => new PaymentMethodConfigType(),
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }
}
