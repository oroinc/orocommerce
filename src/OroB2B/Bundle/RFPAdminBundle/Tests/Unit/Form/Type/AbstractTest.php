<?php

namespace OroB2B\Bundle\RFPAdminBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormTypeInterface;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\RFPAdminBundle\Entity\RequestProduct;
use OroB2B\Bundle\RFPAdminBundle\Entity\RequestProductItem;

abstract class AbstractTest extends FormIntegrationTestCase
{
    /**
     * @var FormTypeInterface
     */
    protected $formType;

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
     * @return PriceType
     */
    protected function preparePriceType()
    {
        $price = new PriceType();
        $price->setDataClass('Oro\Bundle\CurrencyBundle\Model\Price');

        return $price;
    }

    /**
     * @return EntityType
     */
    protected function prepareProductEntityType()
    {
        $products = [];

        $products[2] = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', 2);

        foreach ($this->getProductUnitPrecisions() as $precision) {
            $products[2]->addUnitPrecision($precision);
        }

        $products[3] = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', 3);

        return new EntityType($products);
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
     * @param RequestProductItem[] $items
     * @return RequestProduct
     */
    protected function getRequestProduct($productId, array $items = [])
    {
        $product = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', $productId);

        foreach ($this->getProductUnitPrecisions() as $precision) {
            $product->addUnitPrecision($precision);
        }

        $requestProduct = new RequestProduct();
        $requestProduct->setProduct($product);

        foreach ($items as $item) {
            $requestProduct->addRequestProductItem($item);
        }

        return $requestProduct;
    }

    /**
     * @param int $productId
     * @param int $quantity
     * @param string $unitCode
     * @param Price $price
     * @return RequestProductItem
     */
    protected function getRequestProductItem($productId, $quantity = null, $unitCode = null, Price $price = null)
    {
        $requestProductItem = new RequestProductItem();
        $requestProductItem->setRequestProduct($this->getRequestProduct($productId));

        if (null !== $quantity) {
            $requestProductItem->setQuantity($quantity);
        }

        if (null !== $unitCode) {
            $requestProductItem->setProductUnit(
                $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', $unitCode, 'code')
            );
        }

        if (null !== $price) {
            $requestProductItem->setPrice($price);
        }

        return $requestProductItem;
    }
}
