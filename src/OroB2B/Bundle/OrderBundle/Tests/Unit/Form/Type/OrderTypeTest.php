<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CurrencyBundle\Tests\Unit\Form\Type\PriceTypeGenerator;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;

use OroB2B\Bundle\AccountBundle\Form\Type\AccountSelectType;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserSelectType;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Form\Type\EventListener\SubtotalSubscriber;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderLineItemsCollectionType;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderLineItemType;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderType;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderDiscountItemsCollectionType;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderDiscountItemType;
use OroB2B\Bundle\OrderBundle\Handler\OrderCurrencyHandler;
use OroB2B\Bundle\OrderBundle\Pricing\PriceMatcher;
use OroB2B\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use OroB2B\Bundle\OrderBundle\Provider\DiscountSubtotalProvider;
use OroB2B\Bundle\OrderBundle\Total\TotalHelper;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListSelectType;
use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductSelectTypeStub;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitSelectionTypeStub;
use OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Type\Stub\EntityType as StubEntityType;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;

class OrderTypeTest extends TypeTestCase
{
    use QuantityTypeTrait;

    /** @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade */
    private $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject|OrderAddressSecurityProvider */
    private $orderAddressSecurityProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|PaymentTermProvider */
    private $paymentTermProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|OrderCurrencyHandler */
    private $orderCurrencyHandler;

    /** @var OrderType */
    private $type;

    /** @var \PHPUnit_Framework_MockObject_MockObject|TotalProcessorProvider */
    protected $totalsProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|LineItemSubtotalProvider */
    protected $lineItemSubtotalProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DiscountSubtotalProvider */
    protected $discountSubtotalProvider;

    /** @var PriceMatcher|\PHPUnit_Framework_MockObject_MockObject */
    protected $priceMatcher;

    /** @var ValidatorInterface  */
    private $validator;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()->getMock();
        $this->orderAddressSecurityProvider = $this
            ->getMockBuilder('OroB2B\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider')
            ->disableOriginalConstructor()->getMock();
        $this->paymentTermProvider = $this->getMockBuilder('OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider')
            ->disableOriginalConstructor()->getMock();
        $this->orderCurrencyHandler = $this->getMockBuilder('OroB2B\Bundle\OrderBundle\Handler\OrderCurrencyHandler')
            ->disableOriginalConstructor()->getMock();

        $this->totalsProvider = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->lineItemSubtotalProvider = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->discountSubtotalProvider = $this
            ->getMockBuilder('OroB2B\Bundle\OrderBundle\Provider\DiscountSubtotalProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->priceMatcher = $this->getMockBuilder('OroB2B\Bundle\OrderBundle\Pricing\PriceMatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $totalHelper = new TotalHelper(
            $this->totalsProvider,
            $this->lineItemSubtotalProvider,
            $this->discountSubtotalProvider
        );

        // create a type instance with the mocked dependencies
        $this->type = new OrderType(
            $this->securityFacade,
            $this->orderAddressSecurityProvider,
            $this->paymentTermProvider,
            $this->orderCurrencyHandler,
            new SubtotalSubscriber($totalHelper, $this->priceMatcher)
        );

        $this->type->setDataClass('OroB2B\Bundle\OrderBundle\Entity\Order');
        parent::setUp();
    }

    public function testConfigureOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => 'Order',
                    'intention' => 'order'
                ]
            );

        $this->type->setDataClass('Order');
        $this->type->configureOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals('orob2b_order_type', $this->type->getName());
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param $submitData
     * @param Order $expectedOrder
     */
    public function testSubmitValidData($submitData, $expectedOrder)
    {
        $order = new Order();
        $order->setTotalDiscounts(Price::create(99, 'USD'));

        $options = [
            'data' => $order
        ];

        $this->orderCurrencyHandler->expects($this->any())->method('setOrderCurrency');

        $form = $this->factory->create($this->type, null, $options);

        $subtotal = new Subtotal();
        $subtotal->setAmount(99);
        $this->lineItemSubtotalProvider
            ->expects($this->any())
            ->method('getSubtotal')
            ->willReturn($subtotal);

        $form->submit($submitData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedOrder, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'valid data' => [
                'submitData' => [
                    'sourceEntityClass' => 'Class',
                    'sourceEntityId' => '1',
                    'sourceEntityIdentifier' => '1',
                    'accountUser' => 1,
                    'account' => 2,
                    'poNumber' => '11',
                    'shipUntil' => null,
                    'subtotal' => 0.0,
                    'total' => 0.0,
                    'totalDiscounts' => 0.0,
                    'lineItems' => [
                        [
                            'productSku' => 'HLCU',
                            'product' => 3,
                            'freeFormProduct' => '',
                            'quantity' => 39,
                            'productUnit' => 'piece',
                            'price' => [
                                'value' => 26.5050,
                                'currency' => 'USD',
                            ],
                            'priceType' => 10,
                            'shipBy' => '',
                            'comment' => ''
                        ],
                    ],
                ],
                'expectedOrder' => $this->getOrder(
                    [
                        'sourceEntityClass' => 'Class',
                        'sourceEntityId' => '1',
                        'sourceEntityIdentifier' => '1',
                        'totalDiscounts' => Price::create(99, 'USD'),
                        'accountUser' => 1,
                        'account' => 2,
                        'poNumber' => '11',
                        'shipUntil' => null,
                        'subtotal' => 99,
                        'total' => 0.0,
                        'totalDiscounts' => new Price(),
                        'lineItems' => [
                            [
                                'productSku' => 'HLCU',
                                'product' => 3,
                                'freeFormProduct' => '',
                                'quantity' => 39,
                                'price' => [
                                    'value' => 26.5050,
                                    'currency' => 'USD',
                                ],
                                'priceType' => 10,
                                'comment' => null
                            ],
                        ],
                    ]
                )
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $userSelectType = new StubEntityType(
            [
                1 => $this->getEntity('Oro\Bundle\UserBundle\Entity\User', 1),
                2 => $this->getEntity('Oro\Bundle\UserBundle\Entity\User', 2),
            ],
            'oro_user_select'
        );

        $accountSelectType = new StubEntityType(
            [
                1 => $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', 1),
                2 => $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', 2),
            ],
            AccountSelectType::NAME
        );

        $accountUserSelectType = new StubEntityType(
            [
                1 => $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountUser', 1),
                2 => $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountUser', 2),
            ],
            AccountUserSelectType::NAME
        );

        $priceListSelectType = new StubEntityType(
            [
                1 => $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', 1),
                2 => $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', 2),
            ],
            PriceListSelectType::NAME
        );

        $productUnitSelectionType = $this->prepareProductUnitSelectionType();
        $productSelectType = new ProductSelectTypeStub();
        $entityType = $this->prepareProductEntityType();
        $priceType = $this->preparePriceType();

        /** @var ProductUnitLabelFormatter $ProductUnitLabelFormatter */
        $ProductUnitLabelFormatter = $this
            ->getMockBuilder('OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter')
            ->disableOriginalConstructor()->getMock();

        /** @var ManagerRegistry $managerRegistry */
        $managerRegistry = $this
            ->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()->getMock();

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')->disableOriginalConstructor()->getMock();
        $repository->expects($this->any())->method('findBy')->willReturn([]);
        $managerRegistry->expects($this->any())->method('getRepository')->willReturn($repository);

        $OrderLineItemType = new OrderLineItemType($managerRegistry, $ProductUnitLabelFormatter);
        $OrderLineItemType->setDataClass('OroB2B\Bundle\OrderBundle\Entity\OrderLineItem');
        $currencySelectionType = new CurrencySelectionTypeStub();

        $this->validator = $this->getMock(
            'Symfony\Component\Validator\Validator\ValidatorInterface'
        );
        $this->validator
            ->method('validate')
            ->will($this->returnValue(new ConstraintViolationList()));


        return [
            new PreloadedExtension(
                [
                    CollectionType::NAME => new CollectionType(),
                    OroDateType::NAME => new OroDateType(),
                    $priceType->getName() => $priceType,
                    $entityType->getName() => $entityType,
                    $userSelectType->getName() => $userSelectType,
                    $productSelectType->getName() => $productSelectType,
                    $productUnitSelectionType->getName() => $productUnitSelectionType,
                    $accountSelectType->getName() => $accountSelectType,
                    $currencySelectionType->getName() => $currencySelectionType,
                    $accountUserSelectType->getName() => $accountUserSelectType,
                    $priceListSelectType->getName() => $priceListSelectType,
                    OrderLineItemsCollectionType::NAME => new OrderLineItemsCollectionType(),
                    OrderDiscountItemsCollectionType::NAME => new OrderDiscountItemsCollectionType(),
                    OrderLineItemType::NAME => $OrderLineItemType,
                    OrderDiscountItemType::NAME => new OrderDiscountItemType(),
                    QuantityTypeTrait::$name => $this->getQuantityType(),
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * @param string $className
     * @param int $id
     * @param string $primaryKey
     *
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
     * @return EntityType
     */
    protected function prepareProductUnitSelectionType()
    {
        return new ProductUnitSelectionTypeStub(
            [
                'kg' => $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', 'kg', 'code'),
                'item' => $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', 'item', 'code'),
            ]
        );
    }

    /**
     * @return PriceType
     */
    protected function preparePriceType()
    {
        return PriceTypeGenerator::createPriceType();
    }

    /**
     * @param array $data
     * @return Order
     */
    protected function getOrder($data)
    {
        $order = new Order();
        $accessor = PropertyAccess::createPropertyAccessor();
        foreach ($data as $fieldName => $value) {
            if ($fieldName === 'lineItems') {
                foreach ($value as $lineItem) {
                    $lineItem = $this->getLineItem($lineItem);
                    $order->addLineItem($lineItem);
                }
            } elseif ($fieldName === 'accountUser') {
                $order->setAccountUser($this->getEntity(
                    'OroB2B\Bundle\AccountBundle\Entity\AccountUser',
                    $value
                ));
            } elseif ($fieldName === 'account') {
                $order->setAccount(
                    $this->getEntity(
                        'OroB2B\Bundle\AccountBundle\Entity\Account',
                        $value
                    )
                );
            } else {
                $accessor->setValue($order, $fieldName, $value);
            }
        }

        return $order;
    }

    /**
     * @param array $data
     * @return OrderLineItem
     */
    protected function getLineItem($data)
    {
        $lineItem = new OrderLineItem();
        $accessor = PropertyAccess::createPropertyAccessor();
        foreach ($data as $fieldName => $value) {
            if ($fieldName === 'product') {
                $lineItem->setProduct($this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', $value));
            } elseif ($fieldName === 'price') {
                $price = new Price();
                $price->setCurrency($value['currency']);
                $price->setValue($value['value']);
                $lineItem->setPrice($price);
            } else {
                $accessor->setValue($lineItem, $fieldName, $value);
            }
        }

        return $lineItem;
    }
}
