<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Model\Price;
use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;

use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserSelectType;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountSelectType;

use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\ProductSelectTypeStub;
use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductRemovedSelectType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductSelectType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitRemovedSelectionType;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductRemovedSelectType;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductUnitRemovedSelectionType;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderProduct;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderType;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderProductType;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderProductCollectionType;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderProductItemType;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderProductItemCollectionType;
use OroB2B\Bundle\OrderBundle\Tests\Unit\Form\Type\Stub\EntityType as StubEntityType;

class OrderTypeTest extends AbstractTest
{
    /**
     * @var OrderType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new OrderType();
        $this->formType->setDataClass('OroB2B\Bundle\OrderBundle\Entity\Order');
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => 'OroB2B\Bundle\OrderBundle\Entity\Order',
                    'intention'  => 'order_order',
                    'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"'
                ]
            );
        $this->formType->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals('orob2b_order_order', $this->formType->getName());
    }

    /**
     * @param int $ownerId
     * @param int $accountUserId
     * @param int $accountId
     * @param OrderProduct[] $items
     * @return Order
     */
    protected function getOrder($ownerId, $accountUserId = null, $accountId = null, array $items = [])
    {
        $order = new Order();
        $order->setOwner($this->getEntity('Oro\Bundle\UserBundle\Entity\User', $ownerId));
        $order->setOrganization($this->getEntity('Oro\Bundle\OrganizationBundle\Entity\Organization', $ownerId));

        if (null !== $accountUserId) {
            $order->setAccountUser($this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountUser', $accountUserId));
        }

        if (null !== $accountId) {
            $order->setAccount($this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', $accountId));
        }

        foreach ($items as $item) {
            $order->addOrderProduct($item);
        }

        return $order;
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        $orderProductItem = $this->getOrderProductItem(2, 33, 'kg', self::OPI_PRICE_TYPE1, Price::create(44, 'USD'));
        $orderProduct = $this->getOrderProduct(2, 'comment1', [$orderProductItem]);

        return [
            'empty owner' => [
                'isValid'       => false,
                'submittedData' => [
                ],
                'expectedData'  => new Order(),
            ],
            'empty organization' => [
                'isValid'       => false,
                'submittedData' => [
                    'owner' => 1,
                ],
                'expectedData'  => $this->getOrder(1)->setOrganization(null),
                'defaultData'   => $this->getOrder(1)->setOrganization(null),
            ],
            'invalid identifier' => [
                'isValid'       => false,
                'submittedData' => [
                    'identifier' => '<>',
                    'owner' => 1,
                ],
                'expectedData'  => $this->getOrder(1)->setIdentifier('<>'),
                'defaultData'   => $this->getOrder(1),
            ],
            'valid data' => [
                'isValid'       => true,
                'submittedData' => [
                    'owner' => 1,
                    'accountUser' => 1,
                    'account' => 2,
                    'orderProducts' => [
                        [
                            'product'   => 2,
                            'comment'   => 'comment1',
                            'orderProductItems' => [
                                [
                                    'quantity'      => 33,
                                    'productUnit'   => 'kg',
                                    'priceType'     => self::OPI_PRICE_TYPE1,
                                    'price'         => [
                                        'value'     => 44,
                                        'currency'  => 'USD',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'expectedData'  => $this->getOrder(1, 1, 2, [$orderProduct]),
                'defaultData'   => $this->getOrder(1),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        /* @var $productUnitLabelFormatter ProductUnitLabelFormatter|\PHPUnit_Framework_MockObject_MockObject */
        $productUnitLabelFormatter = $this->getMockBuilder(
            'OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter'
        )
            ->disableOriginalConstructor()
            ->getMock();

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

        $priceType                  = $this->preparePriceType();
        $entityType                 = $this->prepareProductEntityType();
        $productUnitSelectionType   = $this->prepareProductUnitSelectionType();
        $orderProductItemType       = $this->prepareOrderProductItemType();

        $orderProductType = new OrderProductType(
            $productUnitLabelFormatter,
            $this->orderProductFormatter
        );
        $orderProductType->setDataClass('OroB2B\Bundle\OrderBundle\Entity\OrderProduct');

        return [
            new PreloadedExtension(
                [
                    OroDateTimeType::NAME                       => new OroDateTimeType(),
                    CollectionType::NAME                        => new CollectionType(),
                    OrderProductItemType::NAME                  => new OrderProductItemType(
                        $this->orderProductItemFormatter
                    ),
                    OrderProductCollectionType::NAME            => new OrderProductCollectionType(),
                    OrderProductItemCollectionType::NAME        => new OrderProductItemCollectionType(),
                    ProductRemovedSelectType::NAME              => new StubProductRemovedSelectType(),
                    ProductUnitRemovedSelectionType::NAME       => new StubProductUnitRemovedSelectionType(),
                    ProductSelectType::NAME                     => new ProductSelectTypeStub(),
                    CurrencySelectionType::NAME                 => new CurrencySelectionTypeStub(),
                    $priceType->getName()                       => $priceType,
                    $entityType->getName()                      => $entityType,
                    $userSelectType->getName()                  => $userSelectType,
                    $orderProductType->getName()                => $orderProductType,
                    $orderProductItemType->getName()            => $orderProductItemType,
                    $productUnitSelectionType->getName()        => $productUnitSelectionType,
                    $accountSelectType->getName()               => $accountSelectType,
                    $accountUserSelectType->getName()           => $accountUserSelectType,
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }
}
