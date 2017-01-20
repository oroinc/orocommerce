<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Form\EventSubscriber\RuleMethodConfigCollectionSubscriber;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodConfigCollectionType;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodConfigType;
use Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistryInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProvidersRegistry;
use Oro\Bundle\PaymentBundle\Tests\Unit\Form\EventListener\Stub\RuleMethodConfigCollectionSubscriberStub;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Validator\Validation;

class PaymentMethodConfigCollectionTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * @var RuleMethodConfigCollectionSubscriber
     */
    protected $subscriber;

    /**
     * @var PaymentMethodConfigCollectionType
     */
    protected $type;

    protected function setUp()
    {
        parent::setUp();
        $this->subscriber = new RuleMethodConfigCollectionSubscriberStub();
        $this->type = new PaymentMethodConfigCollectionType($this->subscriber);
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
                'existing'  => [
                    new PaymentMethodConfig(),
                    new PaymentMethodConfig(),
                    new PaymentMethodConfig(),
                ],
                'submitted' => [
                    [
                        'type'    => 'PP',
                        'options' => ['client_id' => 5],
                    ],
                    [
                        'type'    => 'MO',
                        'options' => ['client_id' => 5],
                    ],
                    [
                        'type'    => 'PT',
                        'options' => ['client_id' => 5],
                    ]
                ],
                'expected'  => [
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
        /** @var PaymentMethodProvidersRegistryInterface|\PHPUnit_Framework_MockObject_MockObject $methodRegistry */
        $methodRegistry = $this->createMock(PaymentMethodProvidersRegistryInterface::class);
        /** @var PaymentMethodViewProvidersRegistry|\PHPUnit_Framework_MockObject_MockObject $methodViewRegistry */
        $methodViewRegistry = $this->createMock(PaymentMethodViewProvidersRegistry::class);

        return [
            new PreloadedExtension(
                [
                    CollectionType::NAME          => new CollectionType(),
                    PaymentMethodConfigType::NAME => new PaymentMethodConfigType($methodRegistry, $methodViewRegistry),
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
        static::assertSame(CollectionType::class, $this->type->getParent());
    }

    public function testGetBlockPrefix()
    {
        static::assertSame(PaymentMethodConfigCollectionType::NAME, $this->type->getBlockPrefix());
    }

    public function testBuildFormSubscriber()
    {
        /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('addEventSubscriber')
            ->with($this->subscriber)
            ->willReturn($builder);
        $this->type->buildForm($builder, []);
    }
}
