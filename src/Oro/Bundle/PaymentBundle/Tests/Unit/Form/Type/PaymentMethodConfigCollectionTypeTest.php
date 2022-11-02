<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Form\EventSubscriber\RuleMethodConfigCollectionSubscriber;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodConfigCollectionType;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodConfigType;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Method\View\CompositePaymentMethodViewProvider;
use Oro\Bundle\PaymentBundle\Tests\Unit\Form\EventListener\Stub\RuleMethodConfigCollectionSubscriberStub;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormBuilderInterface;
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

    protected function setUp(): void
    {
        $this->subscriber = new RuleMethodConfigCollectionSubscriberStub();
        $this->type = new PaymentMethodConfigCollectionType($this->subscriber);
        parent::setUp();
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array|PaymentMethodConfig[] $existing
     * @param array                       $submitted
     * @param array|PaymentMethodConfig[] $expected
     */
    public function testSubmit(array $existing, array $submitted, array $expected = null)
    {
        $options = [
            'entry_options' => [
                'data_class' => PaymentMethodConfig::class
            ]
        ];

        $form = $this->factory->create(PaymentMethodConfigCollectionType::class, $existing, $options);
        $form->submit($submitted);

        static::assertTrue($form->isValid());
        static::assertTrue($form->isSynchronized());
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
        /** @var PaymentMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject $methodProvider */
        $methodProvider = $this->createMock(PaymentMethodProviderInterface::class);
        /** @var CompositePaymentMethodViewProvider|\PHPUnit\Framework\MockObject\MockObject $methodViewProvider */
        $methodViewProvider = $this->createMock(CompositePaymentMethodViewProvider::class);

        return [
            new PreloadedExtension(
                [
                    $this->type,
                    CollectionType::class          => new CollectionType(),
                    PaymentMethodConfigType::class => new PaymentMethodConfigType($methodProvider, $methodViewProvider),
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
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
        /** @var FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $builder */
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('addEventSubscriber')
            ->with($this->subscriber)
            ->willReturn($builder);
        $this->type->buildForm($builder, []);
    }
}
