<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Converter\RateConverterInterface;
use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerSelectType;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerUserSelectType;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Oro\Bundle\FormBundle\Form\Type\OroHiddenNumberType;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Form\Type\EventListener\SubtotalSubscriber;
use Oro\Bundle\OrderBundle\Form\Type\OrderDiscountCollectionRowType;
use Oro\Bundle\OrderBundle\Form\Type\OrderDiscountCollectionTableType;
use Oro\Bundle\OrderBundle\Form\Type\OrderLineItemsCollectionType;
use Oro\Bundle\OrderBundle\Form\Type\OrderLineItemType;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\OrderBundle\Handler\OrderCurrencyHandler;
use Oro\Bundle\OrderBundle\Handler\OrderLineItemCurrencyHandler;
use Oro\Bundle\OrderBundle\Pricing\PriceMatcher;
use Oro\Bundle\OrderBundle\Provider\DiscountSubtotalProvider;
use Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use Oro\Bundle\OrderBundle\Total\TotalHelper;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectType;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductSelectTypeStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitSelectionTypeStub;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\Type\UserSelectType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;

class OrderTypeTest extends TypeTestCase
{
    use QuantityTypeTrait;

    /** @var OrderCurrencyHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $orderCurrencyHandler;

    /** @var TotalProcessorProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $totalsProvider;

    /** @var LineItemSubtotalProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $lineItemSubtotalProvider;

    /** @var DiscountSubtotalProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $discountSubtotalProvider;

    /** @var RateConverterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $rateConverter;

    /** @var NumberFormatter|\PHPUnit\Framework\MockObject\MockObject */
    private $numberFormatter;

    /** @var OrderType */
    private $type;

    protected function setUp(): void
    {
        $this->orderCurrencyHandler = $this->createMock(OrderCurrencyHandler::class);
        $this->totalsProvider = $this->createMock(TotalProcessorProvider::class);
        $this->lineItemSubtotalProvider = $this->createMock(LineItemSubtotalProvider::class);
        $this->discountSubtotalProvider = $this->createMock(DiscountSubtotalProvider::class);
        $this->rateConverter = $this->createMock(RateConverterInterface::class);

        $totalHelper = new TotalHelper(
            $this->totalsProvider,
            $this->lineItemSubtotalProvider,
            $this->discountSubtotalProvider,
            $this->rateConverter
        );

        $this->numberFormatter = $this->createMock(NumberFormatter::class);

        // create a type instance with the mocked dependencies
        $this->type = new OrderType(
            $this->createMock(OrderAddressSecurityProvider::class),
            $this->orderCurrencyHandler,
            new SubtotalSubscriber(
                $totalHelper,
                $this->createMock(PriceMatcher::class),
                $this->createMock(OrderLineItemCurrencyHandler::class)
            )
        );

        $this->type->setDataClass(Order::class);
        parent::setUp();
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['data_class' => 'Order', 'csrf_token_id' => 'order']);

        $this->type->setDataClass('Order');
        $this->type->configureOptions($resolver);
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmitValidData(array $submitData, Order $expectedOrder)
    {
        $order = new Order();
        $order->setTotalDiscounts(Price::create(99, 'USD'));

        $options = [
            'data' => $order
        ];

        $this->orderCurrencyHandler->expects($this->any())
            ->method('setOrderCurrency');

        $form = $this->factory->create(OrderType::class, null, $options);

        $subtotal = new Subtotal();
        $subtotal->setAmount(99);
        $subtotal->setCurrency('USD');
        $this->lineItemSubtotalProvider->expects($this->any())
            ->method('getSubtotal')
            ->willReturn($subtotal);

        $total = new Subtotal();
        $total->setAmount(0);
        $total->setCurrency('USD');

        $this->totalsProvider->expects($this->once())
            ->method('enableRecalculation')
            ->willReturnSelf();
        $this->totalsProvider->expects($this->once())
            ->method('getTotal')
            ->with($order)
            ->willReturn($total);

        $this->discountSubtotalProvider->expects($this->any())
            ->method('getSubtotal')
            ->willReturn([]);

        $this->rateConverter->expects($this->exactly(2))
            ->method('getBaseCurrencyAmount')
            ->willReturnCallback(function (MultiCurrency $value) {
                return $value->getValue();
            });

        $form->submit($submitData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedOrder, $form->getData());
    }

    public function submitDataProvider(): array
    {
        return [
            'valid data' => [
                'submitData' => [
                    'sourceEntityClass' => 'Class',
                    'sourceEntityId' => '1',
                    'sourceEntityIdentifier' => '1',
                    'customerUser' => 1,
                    'customer' => 2,
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
                    'currency' => 'USD',
                    'shippingMethod' => 'shippingMethod1',
                    'shippingMethodType' => 'shippingType1',
                    'estimatedShippingCostAmount' => 10,
                    'overriddenShippingCostAmount' => [
                        'value' => 5,
                        'currency' => 'USD',
                    ]
                ],
                'expectedOrder' => $this->getOrder(
                    [
                        'sourceEntityClass' => 'Class',
                        'sourceEntityId' => '1',
                        'sourceEntityIdentifier' => '1',
                        'customerUser' => 1,
                        'customer' => 2,
                        'poNumber' => '11',
                        'shipUntil' => null,
                        'subtotalObject' => MultiCurrency::create(99, 'USD', 99),
                        'totalObject' => MultiCurrency::create(0, 'USD', 0),
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
                        'currency' => 'USD',
                        'shippingMethod' => 'shippingMethod1',
                        'shippingMethodType' => 'shippingType1',
                        'estimatedShippingCostAmount' => '10',
                        'overriddenShippingCostAmount' => 5.0
                    ]
                )
            ]
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $priceType = new PriceType();
        $priceType->setDataClass(Price::class);

        $productUnitsProvider = $this->createMock(ProductUnitsProvider::class);
        $productUnitsProvider->expects($this->any())
            ->method('getAvailableProductUnits')
            ->willReturn(['item' => 'item', 'kg' => 'kilogram']);

        $orderLineItemType = new OrderLineItemType($productUnitsProvider);
        $orderLineItemType->setDataClass(OrderLineItem::class);

        return [
            new PreloadedExtension(
                [
                    $this->type,
                    new CollectionType(),
                    new OroDateType(),
                    $priceType,
                    EntityType::class => new EntityTypeStub([
                        2 => $this->getProduct(2),
                        3 => $this->getProduct(3),
                    ]),
                    UserSelectType::class => new EntityTypeStub([
                        1 => $this->getUser(1),
                        2 => $this->getUser(2),
                    ]),
                    ProductSelectType::class => new ProductSelectTypeStub(),
                    ProductUnitSelectionType::class => new ProductUnitSelectionTypeStub([
                        'kg' => $this->getProductUnit('kg'),
                        'item' => $this->getProductUnit('item'),
                    ]),
                    CustomerSelectType::class => new EntityTypeStub([
                        1 => $this->getCustomer(1),
                        2 => $this->getCustomer(2),
                    ]),
                    CurrencySelectionType::class => new CurrencySelectionTypeStub(),
                    CustomerUserSelectType::class => new EntityTypeStub([
                        1 => $this->getCustomerUser(1),
                        2 => $this->getCustomerUser(2),
                    ]),
                    PriceListSelectType::class => new EntityTypeStub([
                        1 => $this->getPriceList(1),
                        2 => $this->getPriceList(2),
                    ]),
                    new OrderLineItemsCollectionType(),
                    new OrderDiscountCollectionTableType(),
                    $orderLineItemType,
                    new OrderDiscountCollectionRowType(),
                    $this->getQuantityType(),
                    new OroHiddenNumberType($this->numberFormatter),
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    private function getUser(int $id): User
    {
        $user = $this->createMock(User::class);
        $user->expects(self::any())
            ->method('getId')
            ->willReturn($id);

        return $user;
    }

    private function getCustomer(int $id): Customer
    {
        $customer = $this->createMock(Customer::class);
        $customer->expects(self::any())
            ->method('getId')
            ->willReturn($id);

        return $customer;
    }

    private function getCustomerUser(int $id): CustomerUser
    {
        $customerUser = $this->createMock(CustomerUser::class);
        $customerUser->expects(self::any())
            ->method('getId')
            ->willReturn($id);

        return $customerUser;
    }

    private function getProduct(int $id): Product
    {
        $product = $this->createMock(Product::class);
        $product->expects(self::any())
            ->method('getId')
            ->willReturn($id);

        return $product;
    }

    private function getProductUnit(string $code): ProductUnit
    {
        $productUnit = $this->createMock(ProductUnit::class);
        $productUnit->expects(self::any())
            ->method('getCode')
            ->willReturn($code);

        return $productUnit;
    }

    private function getPriceList(int $id): PriceList
    {
        $priceList = $this->createMock(PriceList::class);
        $priceList->expects(self::any())
            ->method('getId')
            ->willReturn($id);

        return $priceList;
    }

    private function getOrder(array $data): Order
    {
        $order = new Order();
        $accessor = PropertyAccess::createPropertyAccessor();
        foreach ($data as $fieldName => $value) {
            if ($fieldName === 'lineItems') {
                foreach ($value as $lineItem) {
                    $lineItem = $this->getLineItem($lineItem);
                    $order->addLineItem($lineItem);
                }
            } elseif ($fieldName === 'customerUser') {
                $order->setCustomerUser($this->getCustomerUser($value));
            } elseif ($fieldName === 'customer') {
                $order->setCustomer($this->getCustomer($value));
            } else {
                $accessor->setValue($order, $fieldName, $value);
            }
        }

        return $order;
    }

    private function getLineItem(array $data): OrderLineItem
    {
        $lineItem = new OrderLineItem();
        $accessor = PropertyAccess::createPropertyAccessor();
        foreach ($data as $fieldName => $value) {
            if ($fieldName === 'product') {
                $lineItem->setProduct($this->getProduct($value));
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
