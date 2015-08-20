<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CurrencyBundle\Model\Price;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderLineItemType;
use OroB2B\Bundle\OrderBundle\Form\Type\PriceTypeSelectorType;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductSelectType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;

class OrderLineItemTypeTest extends FormIntegrationTestCase
{
    /**
     * @var OrderLineItemType
     */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->formType = new OrderLineItemType();
        $this->formType->setDataClass('OroB2B\Bundle\OrderBundle\Entity\OrderLineItem');
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $productSelectType = new EntityType(
            [
                1 => $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', 1, 'id', ['sku' => 'SKU1']),
                2 => $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', 2, 'id', ['sku' => 'SKU2']),
            ],
            ProductSelectType::NAME
        );

        $unitSelectType = new EntityType(
            [
                'kg' => $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', 'kg', 'code'),
                'item' => $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', 'item', 'code'),
            ],
            ProductUnitSelectionType::NAME
        );

        $priceType = new PriceType();
        $priceType->setDataClass('Oro\Bundle\CurrencyBundle\Model\Price');

        $orderPriceType = new PriceTypeSelectorType();
        $dateType = new OroDateType();

        return [
            new PreloadedExtension(
                [
                    $productSelectType->getName() => $productSelectType,
                    $unitSelectType->getName() => $unitSelectType,
                    $priceType->getName() => $priceType,
                    $orderPriceType->getName() => $orderPriceType,
                    $dateType->getName() => $dateType,
                ],
                []
            ),
        ];
    }

    public function testConfigureOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'))
            ->will($this->returnSelf());
        $resolver->expects($this->once())
            ->method('setRequired')
            ->with(['currency'])
            ->will($this->returnSelf());

        $this->formType->configureOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals(OrderLineItemType::NAME, $this->formType->getName());
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array $options
     * @param array $submittedData
     * @param OrderLineItem $expectedData
     */
    public function testSubmit(array $options, array $submittedData, OrderLineItem $expectedData)
    {
        $form = $this->factory->create($this->formType, new OrderLineItem(), $options);

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        /** @var Product $product */
        $product = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', 1, 'id', ['sku' => 'SKU1']);
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', '2015-02-03 00:00:00', new \DateTimeZone('UTC'));

        return [
            'default' => [
                'options' => [
                    'currency' => 'USD',
                ],
                'submittedData' => [
                    'product' => 1,
                    'productSku' => '',
                    'freeFormProduct' => '',
                    'quantity' => 10,
                    'productUnit' => 'item',
                    'price' => [
                        'value' => '5',
                        'currency' => 'USD',
                    ],
                    'priceType' => OrderLineItem::PRICE_TYPE_BUNDLED,
                    'shipBy' => '2015-02-03',
                    'comment' => 'Comment',
                ],
                'expectedData' => (new OrderLineItem())
                    ->setProduct($product)
                    ->setQuantity(10)
                    ->setProductUnit($this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', 'item', 'code'))
                    ->setPrice(Price::create(5, 'USD'))
                    ->setPriceType(OrderLineItem::PRICE_TYPE_BUNDLED)
                    ->setShipBy($date)
                    ->setComment('Comment')
            ],
        ];
    }

    /**
     * @param string $className
     * @param int $id
     * @param string $property
     * @param array $data
     * @return object
     */
    protected function getEntity($className, $id, $property = 'id', array $data = [])
    {
        $entity = new $className;

        $reflectionClass = new \ReflectionClass($className);
        $method = $reflectionClass->getProperty($property);
        $method->setAccessible(true);
        $method->setValue($entity, $id);

        if ($data) {
            $propertyAccessor = PropertyAccess::createPropertyAccessor();
            foreach ($data as $key => $value) {
                $propertyAccessor->setValue($entity, $key, $value);
            }
        }

        return $entity;
    }
}
