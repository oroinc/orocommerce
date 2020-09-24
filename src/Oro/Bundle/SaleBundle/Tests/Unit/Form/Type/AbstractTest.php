<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\CurrencyBundle\Tests\Unit\Form\Type\PriceTypeGenerator;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerUserMultiSelectType;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitSelectionTypeStub;
use Oro\Bundle\ProductBundle\Validator\Constraints\QuantityUnitPrecision;
use Oro\Bundle\ProductBundle\Validator\Constraints\QuantityUnitPrecisionValidator;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Entity\QuoteProductRequest;
use Oro\Bundle\SaleBundle\Form\Type\QuoteProductOfferType;
use Oro\Bundle\SaleBundle\Form\Type\QuoteProductRequestType;
use Oro\Bundle\SaleBundle\Formatter\QuoteProductFormatter;
use Oro\Bundle\SaleBundle\Formatter\QuoteProductOfferFormatter;
use Oro\Bundle\SaleBundle\Validator\Constraints;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\Type\UserMultiSelectType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\FormTypeInterface;

abstract class AbstractTest extends FormIntegrationTestCase
{
    const QP_TYPE1          = QuoteProduct::TYPE_REQUESTED;
    const QPO_PRICE_TYPE1   = QuoteProductOffer::PRICE_TYPE_UNIT;

    /**
     * @var FormTypeInterface
     */
    protected $formType;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|QuoteProductFormatter
     */
    protected $quoteProductFormatter;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|QuoteProductOfferFormatter
     */
    protected $quoteProductOfferFormatter;

    protected function configureQuoteProductOfferFormatter()
    {
        $this->quoteProductFormatter = $this->getMockBuilder(
            'Oro\Bundle\SaleBundle\Formatter\QuoteProductFormatter'
        )
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->quoteProductFormatter->expects($this->any())
            ->method('formatTypeLabels')
            ->will($this->returnCallback(function (array $types) {
                return $types;
            }))
        ;

        $this->quoteProductOfferFormatter = $this->getMockBuilder(
            'Oro\Bundle\SaleBundle\Formatter\QuoteProductOfferFormatter'
        )
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->quoteProductOfferFormatter->expects($this->any())
            ->method('formatPriceTypeLabels')
            ->will($this->returnCallback(function (array $types) {
                return $types;
            }))
        ;
    }

    /**
     * @param bool $isValid
     * @param array $submittedData
     * @param mixed $expectedData
     * @param mixed $defaultData
     * @param array $options
     *
     * @dataProvider submitProvider
     */
    public function testSubmit($isValid, $submittedData, $expectedData, $defaultData = null, $options = [])
    {
        $form = $this->factory->create(get_class($this->formType), $defaultData, $options);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);

        $this->assertEquals($isValid, $form->isValid());
        $this->assertTrue($form->isSynchronized());

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
        $quoteProductConstraint = new Constraints\QuoteProduct();

        $UniqueEntity = $this->getMockBuilder('Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator')
        ->disableOriginalConstructor()->getMock();

        $quantityUnitPrecision = new QuantityUnitPrecision();
        $roundingService = $this->createMock(RoundingServiceInterface::class);
        $roundingService->expects($this->any())
            ->method('round')
            ->willReturnCallback(function ($quantity) {
                return (float)$quantity;
            });

        return [
            $quoteProductConstraint->validatedBy() => new Constraints\QuoteProductValidator(),
            'doctrine.orm.validator.unique' => $UniqueEntity,
            $quantityUnitPrecision->validatedBy() => new QuantityUnitPrecisionValidator($roundingService)
        ];
    }

    /**
     * @return QuoteProductOfferType
     */
    protected function prepareQuoteProductOfferType()
    {
        $quoteProductOfferType = new QuoteProductOfferType($this->quoteProductOfferFormatter);
        $quoteProductOfferType->setDataClass('Oro\Bundle\SaleBundle\Entity\QuoteProductOffer');

        return $quoteProductOfferType;
    }

    /**
     * @return QuoteProductRequestType
     */
    protected function prepareQuoteProductRequestType()
    {
        $quoteProductRequestType = new QuoteProductRequestType();
        $quoteProductRequestType->setDataClass('Oro\Bundle\SaleBundle\Entity\QuoteProductRequest');

        return $quoteProductRequestType;
    }

    /**
     * @return PriceType
     */
    protected function preparePriceType()
    {
        return PriceTypeGenerator::createPriceType($this);
    }

    /**
     * @return EntityType
     */
    protected function prepareProductEntityType()
    {
        $entityType = new EntityType(
            [
                2 => $this->getEntity(Product::class, 2),
                3 => $this->getEntity(Product::class, 3),
            ]
        );

        return $entityType;
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
    protected function prepareCustomerUserMultiSelectType()
    {
        return new EntityType(
            [
                10 => $this->getCustomerUser(10),
                11 => $this->getCustomerUser(11),
            ],
            CustomerUserMultiSelectType::NAME,
            [
                'multiple' => true
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
     * @return EntityType
     */
    protected function prepareProductUnitSelectionType()
    {
        return new ProductUnitSelectionTypeStub(
            [
                'kg' => $this->getEntity(ProductUnit::class, 'kg', 'code'),
                'item' => $this->getEntity(ProductUnit::class, 'item', 'code'),
            ]
        );
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
     * @param string $className
     * @param array $fields
     * @return \PHPUnit\Framework\MockObject\MockObject
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
     * @param int $id
     * @return User
     */
    protected function getUser($id)
    {
        return $this->getEntity('Oro\Bundle\UserBundle\Entity\User', $id);
    }

    /**
     * @param int $id
     * @return CustomerUser
     */
    protected function getCustomerUser($id)
    {
        $organization = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface')->getMock();
        $role = $this->createMock('Symfony\Component\Security\Core\Role\Role');

        $customer = $this->getMockBuilder('Oro\Bundle\CustomerBundle\Entity\Customer')->getMock();

        /** @var CustomerUser $customerUser */
        $customerUser = $this->getEntity('Oro\Bundle\CustomerBundle\Entity\CustomerUser', $id);
        $customerUser->setEmail('test@test.test')
            ->setFirstName('First Name')
            ->setLastName('Last Name')
            ->setUsername('test@test.test')
            ->setCustomer($customer)
            ->setRoles([$role])
            ->setOrganization($organization);

        return $customerUser;
    }

    /**
     * @param int $productId
     * @param int $type
     * @param string $comment
     * @param string $commentCustomer
     * @param QuoteProductRequest[] $requests
     * @param QuoteProductOffer[] $offers
     * @return QuoteProduct
     */
    protected function getQuoteProduct(
        $productId = null,
        $type = null,
        $comment = null,
        $commentCustomer = null,
        array $requests = [],
        array $offers = []
    ) {
        $product = null;

        if ($productId) {
            $product = $this->getEntity(Product::class, $productId);

            foreach ($this->getProductUnitPrecisions() as $precision) {
                $product->addUnitPrecision($precision);
            }
        }

        $quoteProduct = new QuoteProduct();
        $quoteProduct
            ->setQuote($this->getEntity(Quote::class, $productId))
            ->setProduct($product)
            ->setType($type)
            ->setComment($comment)
            ->setCommentCustomer($commentCustomer)
        ;

        foreach ($requests as $request) {
            $quoteProduct->addQuoteProductRequest($request);
        }

        foreach ($offers as $offer) {
            $quoteProduct->addQuoteProductOffer($offer);
        }

        return $quoteProduct;
    }

    /**
     * @param int $productId
     * @param float $quantity
     * @param string $unitCode
     * @param int $priceType
     * @param Price $price
     * @return QuoteProductOffer
     */
    protected function getQuoteProductOffer(
        $productId = null,
        $quantity = null,
        $unitCode = null,
        $priceType = null,
        Price $price = null
    ) {
        $quoteProductOffer = new QuoteProductOffer();
        $quoteProductOffer->setQuoteProduct($this->getQuoteProduct($productId));

        if (null !== $quantity) {
            $quoteProductOffer->setQuantity($quantity);
        }

        if (null !== $unitCode) {
            $quoteProductOffer->setProductUnit(
                $this->getEntity(ProductUnit::class, $unitCode, 'code')
            );
        }

        $quoteProductOffer->setPriceType($priceType);

        if (null !== $price) {
            $quoteProductOffer->setPrice($price);
        }

        return $quoteProductOffer;
    }

    /**
     * @param int $productId
     * @param float $quantity
     * @param string $unitCode
     * @param Price $price
     * @return QuoteProductOffer
     */
    protected function getQuoteProductRequest(
        $productId = null,
        $quantity = null,
        $unitCode = null,
        Price $price = null
    ) {
        $quoteProductRequest = new QuoteProductRequest();
        $quoteProductRequest->setQuoteProduct($this->getQuoteProduct($productId));

        if (null !== $quantity) {
            $quoteProductRequest->setQuantity($quantity);
        }

        if (null !== $unitCode) {
            $quoteProductRequest->setProductUnit(
                $this->getEntity(ProductUnit::class, $unitCode, 'code')
            );
        }

        if (null !== $price) {
            $quoteProductRequest->setPrice($price);
        }

        return $quoteProductRequest;
    }
}
