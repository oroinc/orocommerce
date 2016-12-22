<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRuleDestinationPostalCode;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodsConfigsRuleDestinationPostalCodeCollectionType;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodsConfigsRuleDestinationPostalCodeType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Validator\Validation;

class PaymentMethodsConfigsRuleDestinationPostalCodeCollectionTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * @var PaymentMethodsConfigsRuleDestinationPostalCodeCollectionType
     */
    protected $type;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->type = new PaymentMethodsConfigsRuleDestinationPostalCodeCollectionType();
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array|PaymentMethodsConfigsRuleDestinationPostalCode[] $existing
     * @param array $submitted
     * @param array|PaymentMethodsConfigsRuleDestinationPostalCode[] $expected
     */
    public function testSubmit(array $existing, array $submitted, array $expected = null)
    {
        $options = [
            'options' => [
                'data_class' => PaymentMethodsConfigsRuleDestinationPostalCode::class
            ]
        ];

        $form = $this->factory->create($this->type, $existing, $options);
        $form->submit($submitted);

        static::assertTrue($form->isValid());
        static::assertEquals($expected, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'test' => [
                'existing' => [
                    new PaymentMethodsConfigsRuleDestinationPostalCode(),
                    new PaymentMethodsConfigsRuleDestinationPostalCode(),
                ],
                'submitted' => [
                    ['name' => 'code1'],
                    ['name' => 'code2']
                ],
                'expected' => [
                    (new PaymentMethodsConfigsRuleDestinationPostalCode())->setName('code1'),
                    (new PaymentMethodsConfigsRuleDestinationPostalCode())->setName('code2'),
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getExtensions()
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

    public function testGetName()
    {
        static::assertSame(PaymentMethodsConfigsRuleDestinationPostalCodeCollectionType::NAME, $this->type->getName());
    }

    public function testGetParent()
    {
        static::assertSame(CollectionType::NAME, $this->type->getParent());
    }

    public function testGetBlockPrefix()
    {
        static::assertSame(
            PaymentMethodsConfigsRuleDestinationPostalCodeCollectionType::NAME,
            $this->type->getBlockPrefix()
        );
    }
}
