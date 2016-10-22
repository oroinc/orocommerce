<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\OrderBundle\Entity\OrderShippingTracking;
use Oro\Bundle\OrderBundle\Form\Type\OrderShippingTrackingCollectionType;
use Oro\Bundle\OrderBundle\Form\Type\OrderShippingTrackingType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Validator\Validation;

class OrderShippingTrackingCollectionTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * @var OrderShippingTrackingCollectionType
     */
    protected $type;

    protected function setUp()
    {
        parent::setUp();
        $this->type = new OrderShippingTrackingCollectionType();
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array|OrderShippingTracking[] $existing
     * @param array $submitted
     * @param array|OrderShippingTracking[] $expected
     */
    public function testSubmit(array $existing, array $submitted, array $expected = null)
    {
        $options = [
            'options' => [
                'data_class' => OrderShippingTracking::class
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
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    CollectionType::NAME => new CollectionType(),
                    OrderShippingTrackingType::NAME => new OrderShippingTrackingType([]),
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    public function testGetName()
    {
        static::assertSame(OrderShippingTrackingCollectionType::NAME, $this->type->getName());
    }

    public function testGetParent()
    {
        static::assertSame(CollectionType::NAME, $this->type->getParent());
    }

    public function testGetBlockPrefix()
    {
        static::assertSame(OrderShippingTrackingCollectionType::NAME, $this->type->getBlockPrefix());
    }

    /**
     * @dataProvider finishViewDataProvider
     * @param array $options
     */
    public function testFinishView(array $options)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $formView = new FormView();
        $this->type->finishView($formView, $form, $options);

        static::assertArrayHasKey('page_component', $formView->vars);
        static::assertEquals($options['page_component'], $formView->vars['page_component']);

        static::assertArrayHasKey('page_component_options', $formView->vars);
        static::assertEquals($options['page_component_options'], $formView->vars['page_component_options']);
    }

    /**
     * @return array
     */
    public function finishViewDataProvider()
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
