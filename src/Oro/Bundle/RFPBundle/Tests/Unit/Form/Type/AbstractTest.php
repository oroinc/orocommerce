<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitSelectionTypeStub;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\Form\Type\RequestProductItemType;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
abstract class AbstractTest extends FormIntegrationTestCase
{
    protected $dateTimeFields = [
        'updatedAt',
        'createdAt'
    ];

    /** @var FormTypeInterface */
    protected $formType;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit(bool $isValid, array $submittedData, mixed $expectedData, mixed $defaultData = null)
    {
        $form = $this->factory->create(get_class($this->formType), $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);

        $this->assertEquals($isValid, $form->isValid());
        $this->assertTrue($form->isSynchronized());

        $formData = $form->getData();

        $this->checkDateTimeFields($expectedData, $formData);

        $this->fixDateTimeFieldValues($expectedData, $formData);

        $this->assertEquals($expectedData, $form->getData());
    }

    protected function checkDateTimeFields($expectedData, $formData)
    {
        foreach ($this->dateTimeFields as $fieldName) {
            if (method_exists($expectedData, sprintf('get%s', ucfirst($fieldName)))) {
                $expectedDateTimeFieldValue = $this->propertyAccessor->getValue($expectedData, $fieldName);
                $actualDateTimeFieldValue = $this->propertyAccessor->getValue($formData, $fieldName);
                $this->assertGreaterThanOrEqual($expectedDateTimeFieldValue, $actualDateTimeFieldValue);
            }
        }
    }

    protected function fixDateTimeFieldValues($expectedData, $formData)
    {
        $reflectionObj = new \ReflectionObject($expectedData);
        foreach ($this->dateTimeFields as $fieldName) {
            if (method_exists($expectedData, sprintf('get%s', ucfirst($fieldName)))) {
                $actualDateTimeFieldValue = $this->propertyAccessor->getValue($formData, $fieldName);
                $reflectionProperty = $reflectionObj->getProperty($fieldName);
                $reflectionProperty->setAccessible(true);
                $reflectionProperty->setValue($expectedData, $actualDateTimeFieldValue);
            }
        }
    }

    abstract public function submitProvider(): array;

    protected function prepareRequestProductItemType(): RequestProductItemType
    {
        $requestProductItemType = new RequestProductItemType();
        $requestProductItemType->setDataClass(RequestProductItem::class);

        return $requestProductItemType;
    }

    protected function preparePriceType(): PriceType
    {
        $priceType = new PriceType();
        $priceType->setDataClass(Price::class);

        return $priceType;
    }

    protected function prepareProductSelectType(): EntityTypeStub
    {
        $products = [];

        $products[2] = $this->getEntity(Product::class, 2);

        foreach ($this->getProductUnitPrecisions() as $precision) {
            $products[2]->addUnitPrecision($precision);
        }

        $products[3] = $this->getEntity(Product::class, 3);

        return new EntityTypeStub(
            $products,
            [
                'data_parameters' => [],
                'grid_name' => 'products-select-grid-frontend',
                'grid_widget_route' => 'oro_customer_frontend_datagrid_widget',
                'grid_view_widget_route' => 'oro_frontend_datagrid_widget',
                'configs'         => [
                    'placeholder' => null,
                ],
            ]
        );
    }

    /**
     * @param array $codes
     *
     * @return ProductUnit[]
     */
    protected function getProductUnits(array $codes): array
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
    protected function getProductUnitPrecisions(): array
    {
        return [
            (new ProductUnitPrecision())->setUnit((new ProductUnit())->setCode('kg')),
            (new ProductUnitPrecision())->setUnit((new ProductUnit())->setCode('item')),
        ];
    }

    protected function prepareProductUnitSelectionType(): ProductUnitSelectionTypeStub
    {
        return new ProductUnitSelectionTypeStub([
            'kg' => $this->getEntity(ProductUnit::class, 'kg', 'code'),
            'item' => $this->getEntity(ProductUnit::class, 'item', 'code'),
        ]);
    }

    protected function prepareUserMultiSelectType(): EntityTypeStub
    {
        return new EntityTypeStub(
            [1 => $this->getUser(1), 2 => $this->getUser(2)],
            ['multiple' => true]
        );
    }

    protected function prepareCustomerUserMultiSelectType(): EntityTypeStub
    {
        return new EntityTypeStub(
            [10 => $this->getCustomerUser(10), 11 => $this->getCustomerUser(11)],
            ['multiple' => true]
        );
    }

    protected function createRequestProduct(int $id, RequestProduct $product, string $productSku): RequestProduct
    {
        $requestProduct = $this->createMock(RequestProduct::class);
        $requestProduct->expects(static::any())
            ->method('getId')
            ->willReturn($id);
        $requestProduct->expects(static::any())
            ->method('getProduct')
            ->willReturn($product);
        $requestProduct->expects(static::any())
            ->method('getProductSku')
            ->willReturn($productSku);

        return $requestProduct;
    }

    protected function getEntity(string $className, int|string|null $id, string $primaryKey = 'id'): object
    {
        static $entities = [];
        if (!isset($entities[$className][$id])) {
            $entities[$className][$id] = new $className();
            ReflectionUtil::setPropertyValue($entities[$className][$id], $primaryKey, $id);
        }

        return $entities[$className][$id];
    }

    protected function getUser(int $id): User
    {
        return $this->getEntity(User::class, $id);
    }

    protected function getCustomerUser(int $id): CustomerUser
    {
        return $this->getEntity(CustomerUser::class, $id);
    }

    /**
     * @param int                  $productId
     * @param string               $comment
     * @param RequestProductItem[] $items
     *
     * @return RequestProduct
     */
    protected function getRequestProduct(
        int $productId = null,
        string $comment = null,
        array $items = []
    ): RequestProduct {
        /* @var Product|null $product */
        $product = null;
        if ($productId) {
            $product = $this->getEntity(Product::class, $productId);

            foreach ($this->getProductUnitPrecisions() as $precision) {
                $product->addUnitPrecision($precision);
            }
        }

        $requestProduct = new RequestProduct();
        $requestProduct
            ->setRequest($this->getEntity(Request::class, $productId))
            ->setProduct($product)
            ->setComment($comment);

        foreach ($items as $item) {
            $requestProduct->addRequestProductItem($item);
        }

        return $requestProduct;
    }

    protected function getRequestProductItem(
        int $productId,
        int $quantity = null,
        string $unitCode = null,
        Price $price = null
    ): RequestProductItem {
        $requestProductItem = new RequestProductItem();
        $requestProductItem->setRequestProduct($this->getRequestProduct($productId));

        if (null !== $quantity) {
            $requestProductItem->setQuantity($quantity);
        }

        if (null !== $unitCode) {
            $requestProductItem->setProductUnit(
                $this->getEntity(ProductUnit::class, $unitCode, 'code')
            );
        }

        if (null !== $price) {
            $requestProductItem->setPrice($price);
        }

        return $requestProductItem;
    }

    protected function getRequest(
        string $firstName = null,
        string $lastName = null,
        string $email = null,
        string $note = null,
        string $company = null,
        string $role = null,
        string $phone = null,
        string $poNumber = null,
        \DateTime $shipUntil = null
    ): Request {
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
