<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroHiddenNumberType;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
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
     * {@inheritDoc}
     */
    protected function getExtensions(): array
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
        self::assertEquals(OrderCollectionTableType::class, $formType->getParent());
    }

    public function testDefaultOptions()
    {
        $form = $this->factory->create(OrderDiscountCollectionTableType::class, null, ['order' => new Order()]);

        $options = $form->getConfig()->getOptions();

        $this->assertSame('@OroOrder/Discount/order_discount_collection.html.twig', $options['template_name']);
        $this->assertSame('oroui/js/app/components/view-component', $options['page_component']);
        $this->assertSame([
            'view' => 'oroorder/js/app/views/discount-collection-view',
            'discountType' => DiscountSubtotalProvider::TYPE,
            'totalType' => LineItemSubtotalProvider::TYPE,
            'percentType' => OrderDiscount::TYPE_PERCENT
        ], $options['page_component_options']);
        $this->assertSame(['class' => 'oro-discount-collection'], $options['attr']);
        $this->assertSame(OrderDiscountCollectionRowType::class, $options['entry_type']);
    }

    /**
     * @dataProvider orderOptionRequiredDataProvider
     */
    public function testOrderOptionRequired(string $exception, string $exceptionMessage, array $formOptions)
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($exceptionMessage);
        $this->factory->create(OrderDiscountCollectionTableType::class, null, $formOptions);
    }

    public function orderOptionRequiredDataProvider(): array
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
        self::assertEquals('oro_order_discount_collection_table', $formType->getBlockPrefix());
    }

    public function testView()
    {
        $order = new Order();
        $form = $this->factory->create(OrderDiscountCollectionTableType::class, null, [
            'order' => $order
        ]);
        $formView = $form->createView();

        $this->assertSame($order, $formView->vars['order']);
    }
}
