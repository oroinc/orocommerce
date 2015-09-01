<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\PreloadedExtension;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;

use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Form\Type\FrontendOrderLineItemType;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\PricingBundle\Form\Type\ProductPriceListAwareSelectType;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Model\FrontendPriceListRequestHandler;

class FrontendOrderLineItemTypeTest extends AbstractOrderLineItemTypeTest
{
    const PRICE_CLASS = 'priceClass';

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var FrontendPriceListRequestHandler|\PHPUnit_Framework_MockObject_MockObject */
    protected $priceListRequestHandler;

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $productSelectType = new EntityType(
            [
                1 => $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', 1, 'id'),
                2 => $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', 2, 'id'),
            ],
            ProductPriceListAwareSelectType::NAME
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
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Model\FrontendPriceListRequestHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new FrontendOrderLineItemType(
            $this->registry,
            $this->priceListRequestHandler,
            self::PRICE_CLASS
        );

        $priceList = new PriceList();

        $this->priceListRequestHandler->expects($this->any())
            ->method('getPriceList')
            ->willReturn($priceList);

        $this->formType->setDataClass('OroB2B\Bundle\OrderBundle\Entity\OrderLineItem');
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

        $priceRepository = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository')
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
        $product = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', 1, 'id');
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
                    ->setProductUnit($this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', 'item', 'code'))
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
                    ->setProductUnit($this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', 'kg', 'code'))
                    ->setPriceType(OrderLineItem::PRICE_TYPE_UNIT)
                    ->setShipBy($date)
                    ->setComment('Comment'),
                'data' => (new OrderLineItem())
                    ->setOrder($order)
                    ->setFromExternalSource(true)
                    ->setProduct($product)
                    ->setQuantity(5)
                    ->setProductUnit($this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', 'kg', 'code'))
                    ->setPriceType(OrderLineItem::PRICE_TYPE_UNIT)
                    ->setShipBy($date)
                    ->setComment('Comment'),
                'choices' => []
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
                    ->setProductUnit($this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', 'item', 'code'))
                    ->setPriceType(OrderLineItem::PRICE_TYPE_UNIT)
                    ->setShipBy($date)
                    ->setComment('Comment'),
                'data' => (new OrderLineItem())
                    ->setOrder($order)
                    ->setFromExternalSource(true)
                    ->setProduct($product)
                    ->setQuantity(5)
                    ->setProductUnit($this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', 'item', 'code'))
                    ->setPriceType(OrderLineItem::PRICE_TYPE_UNIT)
                    ->setShipBy($date)
                    ->setComment('Comment'),
                'choices' => [
                    $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', 'item', 'code')
                ]
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
                    ->setProductUnit($this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', 'item', 'code'))
                    ->setPriceType(OrderLineItem::PRICE_TYPE_UNIT)
                    ->setComment('Comment Updated'),
                'data' => (new OrderLineItem())
                    ->setOrder($order)
                    ->setFromExternalSource(false)
                    ->setProductSku('SKU')
                    ->setFreeFormProduct('Service')
                    ->setQuantity(5)
                    ->setProductUnit($this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', 'item', 'code'))
                    ->setPriceType(OrderLineItem::PRICE_TYPE_UNIT)
                    ->setComment('Comment'),
                'choices' => [
                    $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', 'item', 'code')
                ]
            ],
        ];
    }
}
