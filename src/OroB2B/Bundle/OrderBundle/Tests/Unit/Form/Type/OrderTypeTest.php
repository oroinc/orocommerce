<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\PreloadedExtension;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CurrencyBundle\Tests\Unit\Form\Type\PriceTypeGenerator;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;

use OroB2B\Bundle\AccountBundle\Form\Type\AccountSelectType;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserSelectType;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderLineItemsCollectionType;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderLineItemType;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderType;
use OroB2B\Bundle\OrderBundle\Model\OrderCurrencyHandler;
use OroB2B\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListSelectType;
use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductSelectTypeStub;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitSelectionTypeStub;
use OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Type\Stub\EntityType as StubEntityType;

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

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()->getMock();
        $this->orderAddressSecurityProvider = $this
            ->getMockBuilder('OroB2B\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider')
            ->disableOriginalConstructor()->getMock();
        $this->paymentTermProvider = $this->getMockBuilder('OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider')
            ->disableOriginalConstructor()->getMock();
        $this->orderCurrencyHandler = $this->getMockBuilder('OroB2B\Bundle\OrderBundle\Model\OrderCurrencyHandler')
            ->disableOriginalConstructor()->getMock();

        // create a type instance with the mocked dependencies
        $this->type = new OrderType(
            $this->securityFacade,
            $this->orderAddressSecurityProvider,
            $this->paymentTermProvider,
            $this->orderCurrencyHandler
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
                    'intention' => 'order',
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
        $options = [
            'data' => $order
        ];

        $this->orderCurrencyHandler->expects($this->any())->method('setOrderCurrency');

        $form = $this->factory->create($this->type, null, $options);

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
                        'accountUser' => 1,
                        'account' => 2,
                        'poNumber' => '11',
                        'shipUntil' => null,
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
                    OrderLineItemType::NAME => $OrderLineItemType,
                    QuantityTypeTrait::$name => $this->getQuantityType(),
                ],
                []
            ),
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

    protected function getOrder($data)
    {
        $order = new Order();

        if (isset($data['sourceEntityClass'])) {
            $order->setSourceEntityClass($data['sourceEntityClass']);
        }

        if (isset($data['sourceEntityId'])) {
            $order->setSourceEntityId($data['sourceEntityId']);
        }

        if (isset($data['sourceEntityIdentifier'])) {
            $order->setSourceEntityIdentifier($data['sourceEntityIdentifier']);
        }

        if (isset($data['poNumber'])) {
            $order->setPoNumber($data['poNumber']);
        }

        if (isset($data['lineItems']) && count($data['lineItems']) > 0) {
            foreach ($data['lineItems'] as $lineItem) {
                $lineItem = $this->getLineItem($lineItem);
                $order->addLineItem($lineItem);
            }
        }

        if (isset($data['accountUser'])) {
            $order->setAccountUser($this->getEntity(
                'OroB2B\Bundle\AccountBundle\Entity\AccountUser',
                $data['accountUser']
            ));
        }

        if (isset($data['account'])) {
            $order->setAccount(
                $this->getEntity(
                    'OroB2B\Bundle\AccountBundle\Entity\Account',
                    $data['account']
                )
            );
        }


        return $order;
    }

    protected function getLineItem($data)
    {
        $lineItem = new OrderLineItem();

        if (isset($data['productSku'])) {
            $lineItem->setProductSku($data['productSku']);
        }

        if (isset($data['freeFormProduct'])) {
            $lineItem->setFreeFormProduct($data['freeFormProduct']);
        }

        if (isset($data['quantity'])) {
            $lineItem->setQuantity($data['quantity']);
        }

        if (isset($data['comment'])) {
            $lineItem->setComment($data['comment']);
        }

        if (isset($data['priceType'])) {
            $lineItem->setPriceType($data['priceType']);
        }

        if (isset($data['product'])) {
            $lineItem->setProduct($this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', $data['product']));
        }

        if (isset($data['price'])) {
            $price = new Price();
            $price->setCurrency($data['price']['currency']);
            $price->setValue($data['price']['value']);
            $lineItem->setPrice($price);
        }

        return $lineItem;
    }
}
