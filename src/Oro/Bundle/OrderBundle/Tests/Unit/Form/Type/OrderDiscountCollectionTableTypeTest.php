<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\OrderBundle\Form\Type\OrderDiscountCollectionRowType;
use Oro\Bundle\OrderBundle\Form\Type\OrderDiscountCollectionTableType;
use Oro\Bundle\OrderBundle\Provider\DiscountSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\PreloadedExtension;

class OrderDiscountCollectionTableTypeTest extends FormIntegrationTestCase
{
    /**
     * @var OrderDiscountCollectionTableType
     */
    protected $type;

    protected function setUp()
    {
        parent::setUp();
        $this->type = new OrderDiscountCollectionTableType();
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array|OrderDiscount[] $existing
     * @param array $submitted
     * @param array|OrderDiscount[] $expected
     */
    public function testSubmit(array $existing, array $submitted, array $expected = null)
    {
        $options = [
            'options' => [
                'data_class' => OrderDiscount::class
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
                    new OrderDiscount(),
                    new OrderDiscount(),
                    new OrderDiscount(),
                ],
                'submitted' => [
                    [
                        'type' => OrderDiscount::TYPE_AMOUNT,
                        'description' => 'anything',
                        'percent' => '',
                        'amount' => '123',
                    ],
                    [
                        'type' => OrderDiscount::TYPE_PERCENT,
                        'description' => '',
                        'percent' => '20',
                        'amount' => '',
                    ],
                ],
                'expected' => [
                    (new OrderDiscount())
                        ->setType(OrderDiscount::TYPE_AMOUNT)->setDescription('anything')->setAmount(123),
                    (new OrderDiscount())->setType(OrderDiscount::TYPE_PERCENT)->setPercent(20),
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
                    'collection' => new CollectionType(),
                    OrderDiscountCollectionRowType::NAME => new OrderDiscountCollectionRowType(),
                ],
                []
            ),
            $this->getValidatorExtension(false)
        ];
    }

    public function testGetName()
    {
        static::assertSame(OrderDiscountCollectionTableType::NAME, $this->type->getName());
    }

    public function testGetParent()
    {
        static::assertSame('collection', $this->type->getParent());
    }

    public function testGetBlockPrefix()
    {
        static::assertSame(OrderDiscountCollectionTableType::NAME, $this->type->getBlockPrefix());
    }

    public function testFinishView()
    {
        $options = [
            'page_component' => 'page_component1',
            'page_component_options' => ['option' => 'value'],
        ];

        $form = $this->factory->create($this->type, null, $options);

        $expectedVars = [
            'attr' => [
                'data-page-component-module' => 'page_component1',
                'data-page-component-options' => '{"option":"value"}',
            ]
        ];

        static::assertArraySubset($expectedVars, $form->createView()->vars);
    }

    public function testDefaultOptions()
    {
        $form = $this->factory->create($this->type);
        $expectedDefaultOptions = [
            'page_component' => 'oroui/js/app/components/view-component',
            'page_component_options' => [
                'view' => 'oroorder/js/app/views/discount-items-view',
                'discountType' => DiscountSubtotalProvider::TYPE,
                'totalType' => LineItemSubtotalProvider::TYPE,
            ],
            'type' => OrderDiscountCollectionRowType::NAME,
            'error_bubbling' => false,
            'prototype' => true,
            'allow_add' => true,
            'allow_delete' => true,
            'prototype_name' => '__order_discount_row__',
            'by_reference' => false,
        ];
        static::assertArraySubset($expectedDefaultOptions, $form->getConfig()->getOptions());
    }
}
