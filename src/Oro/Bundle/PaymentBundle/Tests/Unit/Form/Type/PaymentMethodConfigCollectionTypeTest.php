<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodConfigCollectionType;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodConfigType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Validator\Validation;

class PaymentMethodConfigCollectionTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * @var PaymentMethodConfigCollectionType
     */
    protected $type;

    protected function setUp()
    {
        parent::setUp();
        $this->type = new PaymentMethodConfigCollectionType();
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array|PaymentMethodConfig[] $existing
     * @param array $submitted
     * @param array|PaymentMethodConfig[] $expected
     */
    public function testSubmit(array $existing, array $submitted, array $expected = null)
    {
        $options = [
            'options' => [
                'data_class' => PaymentMethodConfig::class
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
                    new PaymentMethodConfig(),
                    new PaymentMethodConfig(),
                    new PaymentMethodConfig(),
                ],
                'submitted' => [
                    [
                        'type' => 'PP',
                        'options' => ['client_id' => 5],
                    ],
                    [
                        'type' => 'MO',
                        'options' => ['client_id' => 5],
                    ],
                    [
                        'type' => 'PT',
                        'options' => ['client_id' => 5],
                    ]
                ],
                'expected' => [
                    (new PaymentMethodConfig())->setType('PP')->setOptions(['client_id' => 5]),
                    (new PaymentMethodConfig())->setType('MO')->setOptions(['client_id' => 5]),
                    (new PaymentMethodConfig())->setType('PT')->setOptions(['client_id' => 5]),
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
                    PaymentMethodConfigType::NAME => new PaymentMethodConfigType(),
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    public function testGetName()
    {
        static::assertSame(PaymentMethodConfigCollectionType::NAME, $this->type->getName());
    }

    public function testGetParent()
    {
        static::assertSame(CollectionType::NAME, $this->type->getParent());
    }

    public function testGetBlockPrefix()
    {
        static::assertSame(PaymentMethodConfigCollectionType::NAME, $this->type->getBlockPrefix());
    }
}
