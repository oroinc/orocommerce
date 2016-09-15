<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\PreloadedExtension;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Form\Type\FrontendOrderLineItemType;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;

class FrontendOrderLineItemTypeTest extends AbstractOrderLineItemTypeTest
{
    const PRICE_CLASS = 'priceClass';

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var PriceListRequestHandler|\PHPUnit_Framework_MockObject_MockObject */
    protected $priceListRequestHandler;

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $productSelectType = new EntityType(
            [
                1 => $this->getEntity('Oro\Bundle\ProductBundle\Entity\Product', ['id' => 1]),
                2 => $this->getEntity('Oro\Bundle\ProductBundle\Entity\Product', ['id' => 2]),
            ],
            ProductSelectType::NAME,
            [
                'data_parameters' => [],
            ]
        );

        return array_merge(
            parent::getExtensions(),
            [new PreloadedExtension([$productSelectType->getName() => $productSelectType], [])]
        );
    }

    protected function setUp()
    {
        parent::setUp();

        $this->registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->priceListRequestHandler = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\Model\PriceListRequestHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = $this->getFormType();
        $this->formType->setSectionProvider($this->sectionProvider);

        $priceList = new PriceList();

        $this->priceListRequestHandler->expects($this->any())
            ->method('getPriceListByAccount')
            ->willReturn($priceList);

        $this->formType->setDataClass('Oro\Bundle\OrderBundle\Entity\OrderLineItem');
    }

    /** {@inheritdoc} */
    public function getFormType()
    {
        return new FrontendOrderLineItemType(
            $this->registry,
            $this->priceListRequestHandler,
            self::PRICE_CLASS
        );
    }

    public function testGetName()
    {
        $this->assertEquals(FrontendOrderLineItemType::NAME, $this->formType->getName());
    }

    /**
     * @dataProvider buildViewDataProvider
     * @param object|null $data
     * @param bool $expected
     */
    public function testBuildView($data, $expected)
    {
        $this->sectionProvider->expects($this->atLeastOnce())->method('addSections')
            ->with($this->formType->getName(), $this->isType('array'))
            ->willReturn($this->getExpectedSections());

        $view = new FormView();
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($data));
        $options = ['currency' => 'USD'];
        $this->formType->buildView($view, $form, $options);

        $this->assertEquals($expected, $view->vars['disallow_delete']);

        $this->assertDefaultBuildViewCalled();
    }

    /** {@inheritdoc} */
    protected function getExpectedSections()
    {
        return new ArrayCollection(
            [
                'quantity' => ['data' => ['quantity' => [], 'productUnit' => []], 'order' => 10],
                'price' => ['data' => ['price' => []], 'order' => 20],
                'ship_by' => ['data' => ['shipBy' => []], 'order' => 30],
                'comment' => [
                    'data' => [
                        'comment' => ['page_component' => 'oroorder/js/app/components/notes-component'],
                    ],
                    'order' => 40,
                ],
            ]
        );
    }

    /**
     * @return array
     */
    public function buildViewDataProvider()
    {
        return [
            [null, false],
            [(new OrderLineItem()), false],
            [(new OrderLineItem())->setFromExternalSource(true), true],
        ];
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array $options
     * @param array $submittedData
     * @param OrderLineItem $expectedData
     * @param OrderLineItem|null $data
     * @param array $choices
     */
    public function testSubmit(
        array $options,
        array $submittedData,
        OrderLineItem $expectedData,
        OrderLineItem $data = null,
        array $choices = []
    ) {
        if (!$data) {
            $data = new OrderLineItem();
        }

        $em = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $priceRepository = $this->getMockBuilder('Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $priceRepository->expects($this->any())
            ->method('getProductUnitsByPriceList')
            ->willReturn($choices);

        $em->expects($this->any())
            ->method('getRepository')
            ->with(self::PRICE_CLASS)
            ->willReturn($priceRepository);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(self::PRICE_CLASS)
            ->willReturn($em);

        $form = $this->factory->create($this->formType, $data, $options);

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function submitDataProvider()
    {
        /** @var Product $product */
        $product = $this->getEntity('Oro\Bundle\ProductBundle\Entity\Product', ['id' => 1]);
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', '2015-02-03 00:00:00', new \DateTimeZone('UTC'));
        $currency = 'USD';
        $order = new Order();
        $order->setCurrency($currency);

        return [
            'default' => [
                'options' => [
                    'currency' => $currency,
                ],
                'submittedData' => [
                    'product' => 1,
                    'quantity' => 10,
                    'productUnit' => 'item',
                    'shipBy' => '2015-02-03',
                    'comment' => 'Comment',
                ],
                'expectedData' => (new OrderLineItem())
                    ->setProduct($product)
                    ->setQuantity(10)
                    ->setProductUnit(
                        $this->getEntity('Oro\Bundle\ProductBundle\Entity\ProductUnit', ['code' => 'item'])
                    )
                    ->setPriceType(OrderLineItem::PRICE_TYPE_UNIT)
                    ->setShipBy($date)
                    ->setComment('Comment'),
                'data' => null,
            ],
            'restricted modifications' => [
                'options' => [
                    'currency' => $currency,
                ],
                'submittedData' => [
                    'product' => 2,
                    'quantity' => 10,
                    'productUnit' => 'item',
                    'shipBy' => '2015-05-07',
                    'comment' => 'Comment',
                ],
                'expectedData' => (new OrderLineItem())
                    ->setOrder($order)
                    ->setFromExternalSource(true)
                    ->setProduct($product)
                    ->setQuantity(5)
                    ->setProductUnit(
                        $this->getEntity('Oro\Bundle\ProductBundle\Entity\ProductUnit', ['code' => 'kg'])
                    )
                    ->setPriceType(OrderLineItem::PRICE_TYPE_UNIT)
                    ->setShipBy($date)
                    ->setComment('Comment'),
                'data' => (new OrderLineItem())
                    ->setOrder($order)
                    ->setFromExternalSource(true)
                    ->setProduct($product)
                    ->setQuantity(5)
                    ->setProductUnit(
                        $this->getEntity('Oro\Bundle\ProductBundle\Entity\ProductUnit', ['code' => 'kg'])
                    )
                    ->setPriceType(OrderLineItem::PRICE_TYPE_UNIT)
                    ->setShipBy($date)
                    ->setComment('Comment'),
                'choices' => [],
            ],
            'modifications with choices' => [
                'options' => [
                    'currency' => $currency,
                ],
                'submittedData' => [
                    'product' => 2,
                    'quantity' => 10,
                    'productUnit' => 'item',
                    'shipBy' => '2015-05-07',
                    'comment' => 'Comment',
                ],
                'expectedData' => (new OrderLineItem())
                    ->setOrder($order)
                    ->setFromExternalSource(true)
                    ->setProduct($product)
                    ->setQuantity(5)
                    ->setProductUnit(
                        $this->getEntity('Oro\Bundle\ProductBundle\Entity\ProductUnit', ['code' => 'item'])
                    )
                    ->setPriceType(OrderLineItem::PRICE_TYPE_UNIT)
                    ->setShipBy($date)
                    ->setComment('Comment'),
                'data' => (new OrderLineItem())
                    ->setOrder($order)
                    ->setFromExternalSource(true)
                    ->setProduct($product)
                    ->setQuantity(5)
                    ->setProductUnit(
                        $this->getEntity('Oro\Bundle\ProductBundle\Entity\ProductUnit', ['code' => 'item'])
                    )
                    ->setPriceType(OrderLineItem::PRICE_TYPE_UNIT)
                    ->setShipBy($date)
                    ->setComment('Comment'),
                'choices' => [
                    $this->getEntity('Oro\Bundle\ProductBundle\Entity\ProductUnit', ['code' => 'item']),
                ],
            ],
            'free form' => [
                'options' => [
                    'currency' => $currency,
                ],
                'submittedData' => [
                    'product' => null,
                    'quantity' => 10,
                    'productUnit' => 'item',
                    'comment' => 'Comment Updated',
                ],
                'expectedData' => (new OrderLineItem())
                    ->setOrder($order)
                    ->setFromExternalSource(false)
                    ->setProductSku('SKU')
                    ->setFreeFormProduct('Service')
                    ->setQuantity(10)
                    ->setProductUnit(
                        $this->getEntity('Oro\Bundle\ProductBundle\Entity\ProductUnit', ['code' => 'item'])
                    )
                    ->setPriceType(OrderLineItem::PRICE_TYPE_UNIT)
                    ->setComment('Comment Updated'),
                'data' => (new OrderLineItem())
                    ->setOrder($order)
                    ->setFromExternalSource(false)
                    ->setProductSku('SKU')
                    ->setFreeFormProduct('Service')
                    ->setQuantity(5)
                    ->setProductUnit(
                        $this->getEntity('Oro\Bundle\ProductBundle\Entity\ProductUnit', ['code' => 'item'])
                    )
                    ->setPriceType(OrderLineItem::PRICE_TYPE_UNIT)
                    ->setComment('Comment'),
                'choices' => [
                    $this->getEntity('Oro\Bundle\ProductBundle\Entity\ProductUnit', ['code' => 'item']),
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getExpectedOptions()
    {
        return [
            'currency' => null,
            'data_class' => 'Oro\Bundle\OrderBundle\Entity\OrderLineItem',
            'intention' => 'order_line_item',
            'page_component' => 'oroui/js/app/components/view-component',
            'page_component_options' => [
                'view' => 'oroorder/js/app/views/frontend-line-item-view',
            ],
        ];
    }
}
