<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormTypeInterface;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CurrencyBundle\Form\Type\OptionalPriceType;
use Oro\Bundle\CurrencyBundle\Model\OptionalPrice;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\ProductBundle\Validator\Constraints\ProductUnitHolder;
use OroB2B\Bundle\ProductBundle\Validator\Constraints\ProductUnitHolderValidator;

use OroB2B\Bundle\RFPBundle\Entity\RequestProduct;
use OroB2B\Bundle\RFPBundle\Entity\RequestProductItem;
use OroB2B\Bundle\RFPBundle\Form\Type\RequestProductItemType;

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
     * {@inheritdoc}
     */
    protected function getValidators()
    {
        $productUnitHolderConstraint = new ProductUnitHolder();

        return [
            $productUnitHolderConstraint->validatedBy() => new ProductUnitHolderValidator(),
        ];
    }

    /**
     * @return RequestProductItemType
     */
    protected function prepareRequestProductItemType()
    {
        $requestProductItemType = new RequestProductItemType();
        $requestProductItemType->setDataClass('OroB2B\Bundle\RFPBundle\Entity\RequestProductItem');

        return $requestProductItemType;
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
        $products = [];

        $products[2] = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', 2);

        foreach ($this->getProductUnitPrecisions() as $precision) {
            $products[2]->addUnitPrecision($precision);
        }

        $products[3] = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', 3);

        return new EntityType($products);
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
     * @param float $value
     * @param string $currency
     * @return OptionalPrice
     */
    protected function createPrice($value, $currency)
    {
        return OptionalPrice::create($value, $currency);
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
            $entities[$className][$id] = new $className();
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
     * @param RequestProductItem[] $items
     * @return RequestProduct
     */
    protected function getRequestProduct($productId = null, $comment = null, array $items = [])
    {
        /* @var $product Product */
        $product = null;

        if ($productId) {
            $product = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', $productId);

            foreach ($this->getProductUnitPrecisions() as $precision) {
                $product->addUnitPrecision($precision);
            }
        }

        $requestProduct = new RequestProduct();
        $requestProduct
            ->setRequest($this->getEntity('OroB2B\Bundle\RFPBundle\Entity\Request', $productId))
            ->setProduct($product)
            ->setComment($comment)
        ;

        foreach ($items as $item) {
            $requestProduct->addRequestProductItem($item);
        }

        return $requestProduct;
    }

    /**
     * @param int $productId
     * @param int $quantity
     * @param string $unitCode
     * @param OptionalPrice $price
     * @return RequestProductItem
     */
    protected function getRequestProductItem(
        $productId,
        $quantity = null,
        $unitCode = null,
        OptionalPrice $price = null
    ) {
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
