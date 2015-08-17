<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Symfony\Bridge\Doctrine\Validator\Constraints as DoctrineConstraints;
use Symfony\Component\Form\FormTypeInterface;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use Oro\Bundle\CurrencyBundle\Model\Price;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CurrencyBundle\Form\Type\OptionalPriceType;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;

use OroB2B\Bundle\OrderBundle\Entity\OrderProduct;
use OroB2B\Bundle\OrderBundle\Entity\OrderProductItem;

use OroB2B\Bundle\OrderBundle\Form\Type\OrderProductItemType;
use OroB2B\Bundle\OrderBundle\Formatter\OrderProductFormatter;
use OroB2B\Bundle\OrderBundle\Formatter\OrderProductItemFormatter;

use OroB2B\Bundle\ProductBundle\Validator\Constraints as ProductConstraints;

abstract class AbstractTest extends FormIntegrationTestCase
{
    const OPI_PRICE_TYPE1   = OrderProductItem::PRICE_TYPE_UNIT;

    /**
     * @var FormTypeInterface
     */
    protected $formType;

    /**
     * @var OrderProductFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderProductFormatter;

    /**
     * @var OrderProductItemFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderProductItemFormatter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->orderProductFormatter = $this->getMockBuilder(
            'OroB2B\Bundle\OrderBundle\Formatter\OrderProductFormatter'
        )
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->orderProductFormatter->expects($this->any())
            ->method('formatTypeLabels')
            ->will($this->returnCallback(function (array $types) {
                return $types;
            }))
        ;

        $this->orderProductItemFormatter = $this->getMockBuilder(
            'OroB2B\Bundle\OrderBundle\Formatter\OrderProductItemFormatter'
        )
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->orderProductItemFormatter->expects($this->any())
            ->method('formatPriceTypeLabels')
            ->will($this->returnCallback(function (array $types) {
                return $types;
            }))
        ;

        parent::setUp();
    }

    /**
     * @param bool $isValid
     * @param array $submittedData
     * @param mixed $expectedData
     * @param mixed $defaultData
     *
     * @dataProvider submitProvider
     */
    public function testSubmit($isValid, $submittedData, $expectedData, $defaultData = null)
    {
        $form = $this->factory->create($this->formType, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);

        $this->assertEquals($isValid, $form->isValid());

        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    abstract public function submitProvider();

    /**
     * {@inheritdoc}
     */
    protected function getValidators()
    {
        $uniqueEntityConstraint = new DoctrineConstraints\UniqueEntity(['fields' => []]);
        /* @var $uniqueEntity DoctrineConstraints\UniqueEntityValidator|\PHPUnit_Framework_MockObject_MockObject */
        $uniqueEntity =
            $this->getMockBuilder('Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator')
            ->disableOriginalConstructor()
            ->getMock();

        $productUnitHolderConstraint = new ProductConstraints\ProductUnitHolder();
        return [
            $productUnitHolderConstraint->validatedBy() => new ProductConstraints\ProductUnitHolderValidator(),
            $uniqueEntityConstraint->validatedBy() => $uniqueEntity,
        ];
    }

    /**
     * @return PriceType
     */
    protected function preparePriceType()
    {
        $price = new PriceType();
        $price->setDataClass('Oro\Bundle\CurrencyBundle\Model\Price');

        return $price;
    }

    /**
     * @return OrderProductItemType
     */
    protected function prepareOrderProductItemType()
    {
        $orderProductItemType = new OrderProductItemType($this->orderProductItemFormatter);
        $orderProductItemType->setDataClass('OroB2B\Bundle\OrderBundle\Entity\OrderProductItem');

        return $orderProductItemType;
    }

    /**
     * @return OptionalPriceType
     */
    protected function prepareOptionalPriceType()
    {
        $price = new OptionalPriceType();
        $price->setDataClass('Oro\Bundle\CurrencyBundle\Model\OptionalPrice');

        return $price;
    }

    /**
     * @return EntityType
     */
    protected function prepareProductEntityType()
    {
        $entityType = new EntityType(
            [
                2 => $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', 2),
                3 => $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', 3),
            ]
        );

        return $entityType;
    }

    /**
     * @return ProductUnitPrecision[]
     */
    protected function getProductUnitPrecisions()
    {
        return [
            (new ProductUnitPrecision())->setUnit((new ProductUnit())->setCode('kg')),
            (new ProductUnitPrecision())->setUnit((new ProductUnit())->setCode('item')),
        ];
    }

    /**
     * @param array $codes
     * @return ProductUnit[]
     */
    protected function getProductUnits(array $codes)
    {
        $res = [];

        foreach ($codes as $code) {
            $res[] = (new ProductUnit())->setCode($code);
        }

        return $res;
    }

    /**
     * @return EntityType
     */
    protected function prepareProductUnitSelectionType()
    {
        $productUnitSelectionType = new EntityType(
            [
                'kg'    => $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', 'kg', 'code'),
                'item'  => $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', 'item', 'code'),
            ],
            ProductUnitSelectionType::NAME
        );

        return $productUnitSelectionType;
    }

    /**
     * @param string $className
     * @param array $fields
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockEntity($className, array $fields = [])
    {
        $mock = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        foreach ($fields as $method => $value) {
            $mock->expects($this->any())
                ->method($method)
                ->will($this->returnValue($value))
            ;
        }

        return $mock;
    }

    /**
     * @param string $className
     * @param int $id
     * @param string $primaryKey
     * @return object
     */
    protected function getEntity($className, $id, $primaryKey = 'id')
    {
        static $entities = [];

        if (!isset($entities[$className])) {
            $entities[$className] = [];
        }

        if (!isset($entities[$className][$id])) {
            $entities[$className][$id] = new $className;
            $reflectionClass = new \ReflectionClass($className);
            $method = $reflectionClass->getProperty($primaryKey);
            $method->setAccessible(true);
            $method->setValue($entities[$className][$id], $id);
        }

        return $entities[$className][$id];
    }

    /**
     * @param int $productId
     * @param string $comment
     * @param OrderProductItem[] $items
     * @return OrderProduct
     */
    protected function getOrderProduct(
        $productId = null,
        $comment = null,
        array $items = []
    ) {
        $product = null;

        if ($productId) {
            $product = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', $productId);

            foreach ($this->getProductUnitPrecisions() as $precision) {
                $product->addUnitPrecision($precision);
            }
        }

        $orderProduct = new OrderProduct();
        $orderProduct
            ->setOrder($this->getEntity('OroB2B\Bundle\OrderBundle\Entity\Order', $productId))
            ->setProduct($product)
            ->setComment($comment)
        ;

        foreach ($items as $item) {
            $orderProduct->addOrderProductItem($item);
        }

        return $orderProduct;
    }

    /**
     * @param int $productId
     * @param float $quantity
     * @param string $unitCode
     * @param int $priceType
     * @param Price $price
     * @return OrderProductItem
     */
    protected function getOrderProductItem(
        $productId = null,
        $quantity = null,
        $unitCode = null,
        $priceType = null,
        Price $price = null
    ) {
        $orderProductItem = new OrderProductItem();
        $orderProductItem->setOrderProduct($this->getOrderProduct($productId));

        if (null !== $quantity) {
            $orderProductItem->setQuantity($quantity);
        }

        if (null !== $unitCode) {
            $orderProductItem->setProductUnit(
                $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', $unitCode, 'code')
            );
        }

        if (null !== $priceType) {
            $orderProductItem->setPriceType($priceType);
        }

        if (null !== $price) {
            $orderProductItem->setPrice($price);
        }

        return $orderProductItem;
    }
}
