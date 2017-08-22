<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Oro\Bundle\OrderBundle\Form\Type\OrderCollectionTableType;
use Oro\Bundle\OrderBundle\Form\Type\OrderDiscountCollectionRowType;
use Oro\Bundle\OrderBundle\Form\Type\OrderDiscountCollectionTableType;
use Oro\Bundle\OrderBundle\Provider\DiscountSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\PreloadedExtension;

class OrderDiscountCollectionTableTypeTest extends FormIntegrationTestCase
{
    /**
     * @var OrderDiscountCollectionTableType
     */
    private $formType;

    protected function setUp()
    {
        parent::setUp();
        $this->formType = new OrderDiscountCollectionTableType();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    OrderDiscountCollectionRowType::NAME => new OrderDiscountCollectionRowType(),
                ],
                []
            ),
        ];
    }

    public function testGetParent()
    {
        $this->assertEquals(OrderCollectionTableType::class, $this->formType->getParent());
    }

    public function testDefaultOptions()
    {
        $form = $this->factory->create($this->formType);

        $this->assertArraySubset([
            'template_name' => 'OroOrderBundle:Form:order_discount_collection.html.twig',
            'page_component' => 'oroui/js/app/components/view-component',
            'page_component_options' => [
            'view' => 'oroorder/js/app/views/discount-items-view',
                'discountType' => DiscountSubtotalProvider::TYPE,
                'totalType' => LineItemSubtotalProvider::TYPE,
            ],
            'attr' => ['class' => 'oro-discount-collection'],
            'entry_type' => OrderDiscountCollectionRowType::NAME
        ], $form->getConfig()->getOptions());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_order_discount_collection_table', $this->formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals('oro_order_discount_collection_table', $this->formType->getBlockPrefix());
    }
}
