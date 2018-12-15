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
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class OrderDiscountCollectionTableTypeTest extends FormIntegrationTestCase
{
    /**
     * @var OrderDiscountCollectionTableType
     */
    private $formType;

    /**
     * @var NumberFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $numberFormatter;

    protected function setUp()
    {
        $this->numberFormatter = $this->createMock(NumberFormatter::class);

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
                    OrderDiscountCollectionRowType::class => new OrderDiscountCollectionRowType(),
                    OroHiddenNumberType::class => new OroHiddenNumberType($this->numberFormatter),
                ],
                []
            ),
        ];
    }

    public function testGetParent()
    {
        static::assertEquals(OrderCollectionTableType::class, $this->formType->getParent());
    }

    public function testDefaultOptions()
    {
        $form = $this->factory->create($this->formType, null, ['order' => new Order()]);

        static::assertArraySubset([
            'template_name' => 'OroOrderBundle:Discount:order_discount_collection.html.twig',
            'page_component' => 'oroui/js/app/components/view-component',
            'page_component_options' => [
                'view' => 'oroorder/js/app/views/discount-collection-view',
                'discountType' => DiscountSubtotalProvider::TYPE,
                'totalType' => LineItemSubtotalProvider::TYPE,
            ],
            'attr' => ['class' => 'oro-discount-collection'],
            'entry_type' => OrderDiscountCollectionRowType::NAME
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
        $this->factory->create($this->formType, null, $formOptions);
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

    public function testGetName()
    {
        static::assertEquals('oro_order_discount_collection_table', $this->formType->getName());
    }

    public function testGetBlockPrefix()
    {
        static::assertEquals('oro_order_discount_collection_table', $this->formType->getBlockPrefix());
    }

    public function testView()
    {
        $order = new Order();
        $form = $this->factory->create($this->formType, null, [
            'order' => $order
        ]);
        $formView = $form->createView();

        static::assertArraySubset([
            'order' => $order,
        ], $formView->vars);
    }
}
