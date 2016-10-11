<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormTypeInterface;

use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Tests\Unit\Form\Type\PriceTypeGenerator;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\Type\UserMultiSelectType;

use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\AccountBundle\Form\Type\AccountUserMultiSelectType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitSelectionTypeStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductSelectEntityTypeStub;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\Form\Type\RequestProductItemType;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
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
     * @return RequestProductItemType
     */
    protected function prepareRequestProductItemType()
    {
        $requestProductItemType = new RequestProductItemType();
        $requestProductItemType->setDataClass('Oro\Bundle\RFPBundle\Entity\RequestProductItem');

        return $requestProductItemType;
    }

    /**
     * @return PriceType
     */
    protected function preparePriceType()
    {
        return PriceTypeGenerator::createPriceType();
    }

    /**
     * @return ProductSelectEntityTypeStub
     */
    protected function prepareProductSelectType()
    {
        $products = [];

        $products[2] = $this->getEntity('Oro\Bundle\ProductBundle\Entity\Product', 2);

        foreach ($this->getProductUnitPrecisions() as $precision) {
            $products[2]->addUnitPrecision($precision);
        }

        $products[3] = $this->getEntity('Oro\Bundle\ProductBundle\Entity\Product', 3);

        return new EntityType(
            $products,
            ProductSelectType::NAME,
            [
                'data_parameters' => [],
                'grid_name' => 'products-select-grid-frontend',
                'grid_widget_route' => 'oro_account_frontend_datagrid_widget',
                'grid_view_widget_route' => 'oro_frontend_datagrid_widget',
                'configs'         => [
                    'placeholder' => null,
                ],
            ]
        );
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
     * @return ProductUnitSelectionTypeStub
     */
    protected function prepareProductUnitSelectionType()
    {
        return new ProductUnitSelectionTypeStub(
            [
                'kg' => $this->getEntity('Oro\Bundle\ProductBundle\Entity\ProductUnit', 'kg', 'code'),
                'item' => $this->getEntity('Oro\Bundle\ProductBundle\Entity\ProductUnit', 'item', 'code'),
            ]
        );
    }

    /**
     * @return EntityType
     */
    protected function prepareUserMultiSelectType()
    {
        return new EntityType(
            [
                1 => $this->getUser(1),
                2 => $this->getUser(2),
            ],
            UserMultiSelectType::NAME,
            [
                'multiple' => true
            ]
        );
    }

    /**
     * @return EntityType
     */
    protected function prepareAccountUserMultiSelectType()
    {
        return new EntityType(
            [
                10 => $this->getAccountUser(10),
                11 => $this->getAccountUser(11),
            ],
            AccountUserMultiSelectType::NAME,
            [
                'multiple' => true
            ]
        );
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
     * @param RequestProduct $product
     * @param string $productSku
     * @return \PHPUnit_Framework_MockObject_MockObject|RequestProduct
     */
    protected function createRequestProduct($id, $product, $productSku)
    {
        /* @var $requestProduct \PHPUnit_Framework_MockObject_MockObject|RequestProduct */
        $requestProduct = $this->getMock('Oro\Bundle\RFPBundle\Entity\RequestProduct');
        $requestProduct
            ->expects(static::any())
            ->method('getId')
            ->will(static::returnValue($id))
        ;
        $requestProduct
            ->expects(static::any())
            ->method('getProduct')
            ->will(static::returnValue($product))
        ;
        $requestProduct
            ->expects(static::any())
            ->method('getProductSku')
            ->will(static::returnValue($productSku))
        ;

        return $requestProduct;
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
            $entities[$className][$id] = new $className();
            $reflectionClass = new \ReflectionClass($className);
            $method = $reflectionClass->getProperty($primaryKey);
            $method->setAccessible(true);
            $method->setValue($entities[$className][$id], $id);
        }

        return $entities[$className][$id];
    }

    /**
     * @param int $id
     * @return User
     */
    protected function getUser($id)
    {
        return $this->getEntity('Oro\Bundle\UserBundle\Entity\User', $id);
    }

    /**
     * @param int $id
     * @return AccountUser
     */
    protected function getAccountUser($id)
    {
        return $this->getEntity('Oro\Bundle\AccountBundle\Entity\AccountUser', $id);
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
            $product = $this->getEntity('Oro\Bundle\ProductBundle\Entity\Product', $productId);

            foreach ($this->getProductUnitPrecisions() as $precision) {
                $product->addUnitPrecision($precision);
            }
        }

        $requestProduct = new RequestProduct();
        $requestProduct
            ->setRequest($this->getEntity('Oro\Bundle\RFPBundle\Entity\Request', $productId))
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
     * @param Price $price
     * @return RequestProductItem
     */
    protected function getRequestProductItem(
        $productId,
        $quantity = null,
        $unitCode = null,
        Price $price = null
    ) {
        $requestProductItem = new RequestProductItem();
        $requestProductItem->setRequestProduct($this->getRequestProduct($productId));

        if (null !== $quantity) {
            $requestProductItem->setQuantity($quantity);
        }

        if (null !== $unitCode) {
            $requestProductItem->setProductUnit(
                $this->getEntity('Oro\Bundle\ProductBundle\Entity\ProductUnit', $unitCode, 'code')
            );
        }

        if (null !== $price) {
            $requestProductItem->setPrice($price);
        }

        return $requestProductItem;
    }

    /**
     * @param string $firstName
     * @param string $lastName
     * @param string $email
     * @param string $note
     * @param string $company
     * @param string $role
     * @param string $phone
     * @param string $poNumber
     * @param \DateTime $shipUntil
     * @return Request
     */
    protected function getRequest(
        $firstName = null,
        $lastName = null,
        $email = null,
        $note = null,
        $company = null,
        $role = null,
        $phone = null,
        $poNumber = null,
        $shipUntil = null
    ) {
        $request = new Request();

        $request
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setEmail($email)
            ->setNote($note)
            ->setCompany($company)
            ->setRole($role)
            ->setPhone($phone)
            ->setPoNumber($poNumber)
            ->setShipUntil($shipUntil);

        return $request;
    }
}
