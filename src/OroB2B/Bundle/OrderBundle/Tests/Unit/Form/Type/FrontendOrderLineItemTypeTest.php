<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Form\Type\FrontendOrderLineItemType;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class FrontendOrderLineItemTypeTest extends AbstractOrderLineItemTypeTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new FrontendOrderLineItemType();
        $this->formType->setDataClass('OroB2B\Bundle\OrderBundle\Entity\OrderLineItem');
    }

    public function testGetName()
    {
        $this->assertEquals(FrontendOrderLineItemType::NAME, $this->formType->getName());
    }

    /**
     * @dataProvider buildViewDatProvider
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
        $options = [];
        $this->formType->buildView($view, $form, $options);

        $this->assertEquals($expected, $view->vars['disallow_delete']);

        $this->assertDefaultBuildViewCalled();
    }

    /**
     * @return array
     */
    public function buildViewDatProvider()
    {
        return [
            [null, false],
            [(new OrderLineItem()), false],
            [(new OrderLineItem())->setFromExternalSource(true), true],
        ];
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        /** @var Product $product */
        $product = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', 1, 'id');
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', '2015-02-03 00:00:00', new \DateTimeZone('UTC'));
        $currency = 'USD';

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
                    ->setFromExternalSource(true)
                    ->setProduct($product)
                    ->setQuantity(5)
                    ->setProductUnit($this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', 'kg', 'code'))
                    ->setPriceType(OrderLineItem::PRICE_TYPE_UNIT)
                    ->setShipBy($date)
                    ->setComment('Comment'),
                'data' => (new OrderLineItem())
                    ->setFromExternalSource(true)
                    ->setProduct($product)
                    ->setQuantity(5)
                    ->setProductUnit($this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', 'kg', 'code'))
                    ->setPriceType(OrderLineItem::PRICE_TYPE_UNIT)
                    ->setShipBy($date)
                    ->setComment('Comment')
            ],
        ];
    }
}
