<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitRemovedSelectionType;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductUnitRemovedSelectionType;

use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;

use OroB2B\Bundle\OrderBundle\Entity\OrderProduct;
use OroB2B\Bundle\OrderBundle\Entity\OrderProductItem;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderProductItemType;

class OrderProductItemTypeTest extends AbstractTest
{
    /**
     * @var OrderProductItemType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new OrderProductItemType($this->orderProductItemFormatter);
        $this->formType->setDataClass('OroB2B\Bundle\OrderBundle\Entity\OrderProductItem');
    }

    public function testConfigureOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'data_class'    => 'OroB2B\Bundle\OrderBundle\Entity\OrderProductItem',
                'intention'     => 'order_order_product_item',
                'extra_fields_message'  => 'This form should not contain extra fields: "{{ extra_fields }}"',
            ])
        ;

        $this->formType->configureOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals('orob2b_order_order_product_item', $this->formType->getName());
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function submitProvider()
    {
        return [
            'empty form' => [
                'isValid'       => false,
                'submittedData' => [],
                'expectedData'  => $this->getOrderProductItem(1),
                'defaultData'   => $this->getOrderProductItem(1),
            ],
            'empty order product' => [
                'isValid'       => false,
                'submittedData' => [
                    'quantity'      => 88,
                    'productUnit'   => 'kg',
                    'priceType'     => self::OPI_PRICE_TYPE1,
                    'price'         => [
                        'value'     => 99,
                        'currency'  => 'EUR',
                    ],
                ],
                'expectedData'  => $this
                    ->getOrderProductItem(2, 88, 'kg', self::OPI_PRICE_TYPE1, $this->createPrice(99, 'EUR'))
                    ->setOrderProduct(null),
                'defaultData'   => $this->getOrderProductItem(2)
                    ->setOrderProduct(null),
            ],
            'empty quantity' => [
                'isValid'       => false,
                'submittedData' => [
                    'productUnit'   => 'kg',
                    'priceType'     => self::OPI_PRICE_TYPE1,
                    'price'         => [
                        'value'     => 11,
                        'currency'  => 'EUR',
                    ],
                ],
                'expectedData'  => $this
                    ->getOrderProductItem(3, null, 'kg', self::OPI_PRICE_TYPE1, $this->createPrice(11, 'EUR')),
                'defaultData'   => $this->getOrderProductItem(3),
            ],
            'empty price type' => [
                'isValid'       => false,
                'submittedData' => [
                    'quantity'      => 88,
                    'productUnit'   => 'kg',
                    'price'         => [
                        'value'     => 99,
                        'currency'  => 'EUR',
                    ],
                ],
                'expectedData'  => $this->getOrderProductItem(4, 88, 'kg', null, $this->createPrice(99, 'EUR')),
                'defaultData'   => $this->getOrderProductItem(4),
            ],
            'empty product unit' => [
                'isValid'       => false,
                'submittedData' => [
                    'quantity'      => 22,
                    'priceType'     => self::OPI_PRICE_TYPE1,
                    'price'         => [
                        'value'     => 33,
                        'currency'  => 'EUR',
                    ],
                ],
                'expectedData'  => $this
                    ->getOrderProductItem(5, 22, null, self::OPI_PRICE_TYPE1, $this->createPrice(33, 'EUR')),
                'defaultData'   => $this->getOrderProductItem(5),
            ],
            'empty price' => [
                'isValid'       => false,
                'submittedData' => [
                    'quantity'      => 44,
                    'productUnit'   => 'kg',
                    'priceType'     => self::OPI_PRICE_TYPE1,
                ],
                'expectedData'  => $this->getOrderProductItem(6, 44, 'kg', self::OPI_PRICE_TYPE1),
                'defaultData'   => $this->getOrderProductItem(6),
            ],
            'valid data' => [
                'isValid'       => true,
                'submittedData' => [
                    'quantity'      => 11,
                    'productUnit'   => 'kg',
                    'priceType'     => self::OPI_PRICE_TYPE1,
                    'price'         => [
                        'value'     => 22,
                        'currency'  => 'EUR',
                    ],
                ],
                'expectedData'  => $this
                    ->getOrderProductItem(7, 11, 'kg', self::OPI_PRICE_TYPE1, $this->createPrice(22, 'EUR')),
                'defaultData'   => $this->getOrderProductItem(7),
            ],
        ];
    }

    /**
     * @param float $value
     * @param string $currency
     * @return Price
     */
    protected function createPrice($value, $currency)
    {
        return Price::create($value, $currency);
    }

    /**
     * @param int $id
     * @param ProductUnit[] $productUnits
     * @param string $unitCode
     * @return OrderProductItem|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createOrderProductItem($id, array $productUnits = [], $unitCode = null)
    {
        $productUnit = null;

        $product = new Product();
        foreach ($productUnits as $unit) {
            $product->addUnitPrecision((new ProductUnitPrecision())->setUnit($unit));

            if ($unitCode && $unit->getCode() === $unitCode) {
                $productUnit = $unit;
            }
        }

        /* @var $item OrderProductItem|\PHPUnit_Framework_MockObject_MockObject */
        $item = $this->getMock('OroB2B\Bundle\OrderBundle\Entity\OrderProductItem');
        $item
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id))
        ;
        $item
            ->expects($this->any())
            ->method('getProductUnit')
            ->will($this->returnValue($productUnit))
        ;
        $item
            ->expects($this->any())
            ->method('getProductUnitCode')
            ->will($this->returnValue($unitCode))
        ;
        $item
            ->expects($this->any())
            ->method('getOrderProduct')
            ->will($this->returnValue((new OrderProduct())->setProduct($product)))
        ;

        return $item;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $priceType                  = $this->preparePriceType();
        $currencySelectionType      = new CurrencySelectionTypeStub();
        $productUnitSelectionType   = $this->prepareProductUnitSelectionType();

        return [
            new PreloadedExtension(
                [
                    ProductUnitRemovedSelectionType::NAME   => new StubProductUnitRemovedSelectionType(),
                    $priceType->getName()                   => $priceType,
                    $currencySelectionType->getName()       => $currencySelectionType,
                    $productUnitSelectionType->getName()    => $productUnitSelectionType,
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }
}
