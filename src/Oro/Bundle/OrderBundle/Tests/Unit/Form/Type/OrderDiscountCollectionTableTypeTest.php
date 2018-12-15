<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroHiddenNumberType;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Form\Type\OrderCollectionTableType;
use Oro\Bundle\OrderBundle\Form\Type\OrderDiscountCollectionRowType;
use Oro\Bundle\OrderBundle\Form\Type\OrderDiscountCollectionTableType;
use Oro\Bundle\OrderBundle\Provider\DiscountSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class OrderDiscountCollectionTableTypeTest extends FormIntegrationTestCase
{
    /**
     * @return array
     */
    protected function getExtensions()
    {
        $numberFormatter = $this->createMock(NumberFormatter::class);

        return [
            new PreloadedExtension(
                [
                    OrderDiscountCollectionRowType::class => new OrderDiscountCollectionRowType(),
                    OroHiddenNumberType::class => new OroHiddenNumberType($numberFormatter),
                ],
                []
            ),
        ];
    }

    public function testGetParent()
    {
        $formType = new OrderDiscountCollectionTableType();
        static::assertEquals(OrderCollectionTableType::class, $formType->getParent());
    }

    public function testDefaultOptions()
    {
        $form = $this->factory->create(OrderDiscountCollectionTableType::class, null, ['order' => new Order()]);

        static::assertArraySubset([
            'template_name' => 'OroOrderBundle:Discount:order_discount_collection.html.twig',
            'page_component' => 'oroui/js/app/components/view-component',
            'page_component_options' => [
                'view' => 'oroorder/js/app/views/discount-collection-view',
                'discountType' => DiscountSubtotalProvider::TYPE,
                'totalType' => LineItemSubtotalProvider::TYPE,
            ],
            'attr' => ['class' => 'oro-discount-collection'],
            'entry_type' => OrderDiscountCollectionRowType::class
        ], $form->getConfig()->getOptions());
    }

    /**
     * @dataProvider orderOptionRequiredDataProvider
     *
     * @param $exception
     * @param $exceptionMessage
     * @param $formOptions
     */
    public function testOrderOptionRequired($exception, $exceptionMessage, $formOptions)
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($exceptionMessage);
        $this->factory->create(OrderDiscountCollectionTableType::class, null, $formOptions);
    }

    public function orderOptionRequiredDataProvider()
    {
        return [
            'order option required' => [
                'exception' => MissingOptionsException::class,
                'exceptionMessage' => 'The required option "order" is missing.',
                'formOptions' => [],
            ],
            'order option should be of type Order::class' => [
                'exception' => InvalidOptionsException::class,
                'exceptionMessage' => 'The option "order" with value "anything" is expected to be of type'
                    .' "Oro\Bundle\OrderBundle\Entity\Order", but is of type "string".',
                'formOptions' => ['order' => 'anything'],
            ],
        ];
    }

    public function testGetBlockPrefix()
    {
        $formType = new OrderDiscountCollectionTableType();
        static::assertEquals('oro_order_discount_collection_table', $formType->getBlockPrefix());
    }

    public function testView()
    {
        $order = new Order();
        $form = $this->factory->create(OrderDiscountCollectionTableType::class, null, [
            'order' => $order
        ]);
        $formView = $form->createView();

        static::assertArraySubset([
            'order' => $order,
        ], $formView->vars);
    }
}
