<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Form\FormView;

use Doctrine\Common\Collections\ArrayCollection;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Form\Extension\OrderDataStorageExtension;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Extension\AbstractProductDataStorageExtensionTestCase;

class OrderDataStorageExtensionTest extends AbstractProductDataStorageExtensionTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $this->request = $this->getMock('Symfony\Component\HttpFoundation\Request');

        $requestStack->expects($this->any())->method('getCurrentRequest')->willReturn($this->request);
        $this->entity = new Order();
        $this->extension = new OrderDataStorageExtension(
            $requestStack,
            $this->storage,
            $this->doctrineHelper,
            $this->productClass
        );
        $this->extension->setDataClass('OroB2B\Bundle\OrderBundle\Entity\Order');
    }

    public function testBuild()
    {
        $sku = 'TEST';
        $data = [
            ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                [
                    ProductDataStorage::PRODUCT_SKU_KEY => $sku,
                    'offers' => [
                        'quantity' => 1,
                        'unit' => 'kg',
                        'currency' => 'USD',
                        'price' => 30,
                        'quantityFormatted' => '1 kg',
                        'priceFormatted' => '$30',
                    ],
                ],
            ]
        ];
        $order = new Order();

        $productUnit = new ProductUnit();
        $productUnit->setCode('item');

        $product = $this->getProductEntity($sku, $productUnit);

        $this->assertMetadataCalled();
        $this->assertRequestGetCalled();
        $this->assertStorageCalled($data);
        $this->assertProductRepositoryCalled($product);

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormBuilderInterface $builder */
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $this->extension->buildForm($builder, ['data' => $order]);

        $this->assertCount(1, $order->getLineItems());
        /** @var OrderLineItem $lineItem */
        $lineItem = $order->getLineItems()->first();

        $this->assertEquals($product, $lineItem->getProduct());
        $this->assertEquals($product->getSku(), $lineItem->getProductSku());
        $this->assertEquals($productUnit, $lineItem->getProductUnit());
        $this->assertEquals($productUnit->getCode(), $lineItem->getProductUnitCode());
    }

    public function testSortSections()
    {
        $sections = new ArrayCollection(
            [
                'item3' => [ 'order' => 30 ],
                'item2' => [ 'order' => 20 ],
                'item1' => [ 'order' => 10 ]
            ]
        );

        $reflector = new \ReflectionClass('OroB2B\Bundle\OrderBundle\Form\Extension\OrderDataStorageExtension');
        $method = $reflector->getMethod('sortSections');
        $method->setAccessible(true);

        $sectionsSorted = $method->invokeArgs($this->extension, array($sections));

        $this->assertEquals(10, $sectionsSorted->first()['order']);
    }

    public function testFinishView()
    {
        $data = [
            'withOffers' => 1,
            'entity_items_data' => [
                [
                    'productSku' => 'testSku',
                    'offers' => [
                        [
                            'testOffer' => 'testValue'
                        ]
                    ]
                ]
            ]
        ];

        $lineItem = new FormView();
        $lineItem->vars['sections'] = new ArrayCollection();

        $lineItemValue = $this->getMockBuilder('OroB2B\Bundle\OrderBundle\Entity\OrderLineItem')
            ->disableOriginalConstructor()
            ->getMock();
        $lineItemValue->method('getProductSku')->willReturn('testSku');
        $lineItem->vars['value'] = $lineItemValue;

        $view = new FormView();
        $view->children = ['lineItems' => new FormView()];
        $view->children['lineItems']->children = [$lineItem];
        $view->children['lineItems']->vars['prototype'] = new FormView();
        $view->children['lineItems']->vars['prototype']->vars['sections'] = new ArrayCollection();

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension->finishView($view, $form, ['storage_data' => $data]);

        $this->assertEquals(
            $view->children['lineItems']->children[0]->vars['offers'][0]['testOffer'],
            'testValue'
        );
        $this->assertEquals(
            $view->children['lineItems']->children[0]->vars['sections']['offers']['order'],
            5
        );
        $this->assertEquals(
            $view->children['lineItems']->vars['prototype']->vars['sections']['offers']['order'],
            5
        );
    }
}
