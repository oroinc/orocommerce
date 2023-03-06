<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\OrderBundle\Entity\OrderShippingTracking;
use Oro\Bundle\OrderBundle\Form\Type\OrderShippingTrackingCollectionType;
use Oro\Bundle\OrderBundle\Form\Type\OrderShippingTrackingType;
use Oro\Bundle\ShippingBundle\Method\TrackingAwareShippingMethodsProviderInterface;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Validator\Validation;

class OrderShippingTrackingCollectionTypeTest extends FormIntegrationTestCase
{
    /** @var OrderShippingTrackingCollectionType */
    private $type;

    protected function setUp(): void
    {
        $this->type = new OrderShippingTrackingCollectionType();
        parent::setUp();
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $existing, array $submitted, array $expected = null)
    {
        $options = [
            'entry_options' => [
                'data_class' => OrderShippingTracking::class
            ]
        ];

        $form = $this->factory->create(OrderShippingTrackingCollectionType::class, $existing, $options);
        $form->submit($submitted);

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertEquals($expected, $form->getData());
    }

    public function submitDataProvider(): array
    {
        return [
            'test' => [
                'existing' => [
                    new OrderShippingTracking(),
                    new OrderShippingTracking(),
                    new OrderShippingTracking(),
                ],
                'submitted' => [
                    [
                        'method' => 'UPS',
                        'number' => '1Z111',
                    ],
                    [
                        'method' => 'USPS',
                        'number' => '1Z222',
                    ],
                    [
                        'method' => 'FedEx',
                        'number' => '1Z333',
                    ]
                ],
                'expected' => [
                    (new OrderShippingTracking())->setMethod('UPS')->setNumber('1Z111'),
                    (new OrderShippingTracking())->setMethod('USPS')->setNumber('1Z222'),
                    (new OrderShippingTracking())->setMethod('FedEx')->setNumber('1Z333'),
                ]
            ]
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $trackingAwareShippingMethodsProvider = $this->createMock(TrackingAwareShippingMethodsProviderInterface::class);
        $trackingAwareShippingMethodsProvider->expects(self::any())
            ->method('getTrackingAwareShippingMethods')
            ->willReturn([]);

        return [
            new PreloadedExtension(
                [
                    $this->type,
                    new CollectionType(),
                    new OrderShippingTrackingType($trackingAwareShippingMethodsProvider),
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    public function testGetParent()
    {
        self::assertSame(CollectionType::class, $this->type->getParent());
    }

    public function testGetBlockPrefix()
    {
        self::assertSame('oro_order_shipping_tracking_collection', $this->type->getBlockPrefix());
    }

    /**
     * @dataProvider finishViewDataProvider
     */
    public function testFinishView(array $options)
    {
        $form = $this->createMock(FormInterface::class);

        $formView = new FormView();
        $this->type->finishView($formView, $form, $options);

        self::assertArrayHasKey('page_component', $formView->vars);
        self::assertEquals($options['page_component'], $formView->vars['page_component']);

        self::assertArrayHasKey('page_component_options', $formView->vars);
        self::assertEquals($options['page_component_options'], $formView->vars['page_component_options']);
    }

    public function finishViewDataProvider(): array
    {
        return [
            'test1' => [
                'options' => [
                    'page_component' => 'page_component1',
                    'page_component_options' => [1, 2, 3],
                ],
            ],
            'test2' => [
                'options' => [
                    'page_component' => 'page_component2',
                    'page_component_options' => [3, 2, 1],
                ],
            ],
        ];
    }
}
