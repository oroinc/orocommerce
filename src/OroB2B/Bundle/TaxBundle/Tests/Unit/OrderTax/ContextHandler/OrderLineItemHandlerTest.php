<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\OrderTax\ContextHandler;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TaxBundle\Entity\AccountTaxCode;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Entity\Repository\AbstractTaxCodeRepository;
use Oro\Bundle\TaxBundle\Event\ContextEvent;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
use Oro\Bundle\TaxBundle\OrderTax\ContextHandler\OrderLineItemHandler;
use Oro\Bundle\TaxBundle\Provider\TaxationAddressProvider;

class OrderLineItemHandlerTest extends \PHPUnit_Framework_TestCase
{
    const PRODUCT_TAX_CODE_CLASS = 'PRODUCT_TAX_CODE_CLASS';
    const ACCOUNT_TAX_CODE_CLASS = 'ACCOUNT_TAX_CODE_CLASS';
    const ORDER_LINE_ITEM_CLASS = 'Oro\Bundle\OrderBundle\Entity\OrderLineItem';
    const PRODUCT_TAX_CODE = 'PTC';
    const ACCOUNT_TAX_CODE = 'ATC';
    const ACCOUNT_GROUP_TAX_CODE = 'AGTC';
    const ORDER_ADDRESS_COUNTRY_CODE = 'US';

    /**
     * @var TaxationAddressProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $addressProvider;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var AbstractTaxCodeRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    /**
     * @var OrderLineItemHandler
     */
    protected $handler;

    /**
     * @var ProductTaxCode
     */
    protected $productTaxCode;

    /**
     * @var AccountTaxCode
     */
    protected $accountTaxCode;

    /**
     * @var AccountTaxCode
     */
    protected $accountGroupTaxCode;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var OrderAddress
     */
    protected $address;

    /**
     * @var bool
     */
    protected $isProductTaxCodeDigital = false;

    protected function setUp()
    {
        $this->productTaxCode = (new ProductTaxCode())
            ->setCode(self::PRODUCT_TAX_CODE);

        $this->order = new Order();

        $this->accountTaxCode = (new AccountTaxCode())
            ->setCode(self::ACCOUNT_TAX_CODE);

        $this->accountGroupTaxCode = (new AccountTaxCode())
            ->setCode(self::ACCOUNT_GROUP_TAX_CODE);

        $billingAddress = new OrderAddress();
        $shippingAddress = new OrderAddress();

        $this->order->setBillingAddress($billingAddress);
        $this->order->setShippingAddress($shippingAddress);

        $this->addressProvider = $this
            ->getMockBuilder('Oro\Bundle\TaxBundle\Provider\TaxationAddressProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->addressProvider
            ->expects($this->any())
            ->method('getTaxationAddress')
            ->with($billingAddress, $shippingAddress)
            ->willReturnCallback(
                function () {
                    return $this->address;
                }
            );

        $this->addressProvider
            ->expects($this->any())
            ->method('isDigitalProductTaxCode')
            ->with(self::ORDER_ADDRESS_COUNTRY_CODE, self::PRODUCT_TAX_CODE)
            ->willReturnCallback(
                function () {
                    return $this->isProductTaxCodeDigital;
                }
            );

        $this->repository = $this
            ->getMockBuilder('Oro\Bundle\TaxBundle\Entity\Repository\AbstractTaxCodeRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this
            ->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper
            ->expects($this->any())
            ->method('getEntityRepositoryForClass')
            ->willReturn($this->repository);

        $this->handler = new OrderLineItemHandler(
            $this->addressProvider,
            $this->doctrineHelper,
            self::PRODUCT_TAX_CODE_CLASS,
            self::ACCOUNT_TAX_CODE_CLASS,
            self::ORDER_LINE_ITEM_CLASS
        );
    }

    protected function tearDown()
    {
        unset($this->handler, $this->doctrineHelper, $this->addressProvider);
    }

    /**
     * @dataProvider onContextEventProvider
     * @param bool $hasProduct
     * @param bool $hasAccount
     * @param bool $hasProductTaxCode
     * @param bool $hasAccountTaxCode
     * @param bool $isProductDigital
     * @param OrderAddress|null $taxationAddress
     * @param \ArrayObject $expectedContext
     * @param bool $hasAccountGroup
     * @param bool $hasAccountGroupTaxCode
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function testOnContextEvent(
        $hasProduct,
        $hasAccount,
        $hasProductTaxCode,
        $hasAccountTaxCode,
        $isProductDigital,
        $taxationAddress,
        $expectedContext,
        $hasAccountGroup = false,
        $hasAccountGroupTaxCode = false
    ) {
        $this->isProductTaxCodeDigital = $isProductDigital;
        $this->address = $taxationAddress;

        $orderLineItem = new OrderLineItem();

        if ($hasAccount) {
            $this->order->setAccount(new Account());
        }

        if ($hasAccount && $hasAccountGroup) {
            $this->order->getAccount()->setGroup(new AccountGroup());
        }

        $orderLineItem->setOrder($this->order);

        if ($hasProduct) {
            $orderLineItem->setProduct(new Product());
        }

        if (!$hasProductTaxCode) {
            $this->productTaxCode = null;
        }

        if (!$hasAccountTaxCode) {
            $this->accountTaxCode = null;
        }

        if (!$hasAccountGroupTaxCode) {
            $this->accountGroupTaxCode = null;
        }

        $this->repository
            ->expects($this->atLeastOnce())
            ->method('findOneByEntity')
            ->willReturnCallback(function ($type) {
                switch ($type) {
                    case TaxCodeInterface::TYPE_PRODUCT:
                        return $this->productTaxCode;
                    case TaxCodeInterface::TYPE_ACCOUNT:
                        return $this->accountTaxCode;
                    case TaxCodeInterface::TYPE_ACCOUNT_GROUP:
                        return $this->accountGroupTaxCode;
                }

                return false;
            });

        $contextEvent = new ContextEvent($orderLineItem);
        $this->handler->onContextEvent($contextEvent);

        $this->assertSame($orderLineItem, $contextEvent->getMappingObject());
        $this->assertEquals($expectedContext, $contextEvent->getContext());

        $this->handler->onContextEvent($contextEvent);

        $this->assertSame($orderLineItem, $contextEvent->getMappingObject());
        $this->assertEquals($expectedContext, $contextEvent->getContext());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function onContextEventProvider()
    {
        $taxationAddress = (new OrderAddress())
            ->setCountry(new Country(self::ORDER_ADDRESS_COUNTRY_CODE));

        return [
            'order line item without product' => [
                'hasProduct' => false,
                'hasAccount' => true,
                'hasProductTaxCode' => false,
                'hasAccountTaxCode' => false,
                'isProductDigital' => false,
                'taxationAddress' => $taxationAddress,
                'expectedContext' => new \ArrayObject(
                    [
                        Taxable::DIGITAL_PRODUCT => false,
                        Taxable::PRODUCT_TAX_CODE => null,
                        Taxable::ACCOUNT_TAX_CODE => null,
                    ]
                ),
            ],
            'product is not digital' => [
                'hasProduct' => true,
                'hasAccount' => true,
                'hasProductTaxCode' => true,
                'hasAccountTaxCode' => false,
                'isProductDigital' => false,
                'taxationAddress' => $taxationAddress,
                'expectedContext' => new \ArrayObject(
                    [
                        Taxable::DIGITAL_PRODUCT => false,
                        Taxable::PRODUCT_TAX_CODE => self::PRODUCT_TAX_CODE,
                        Taxable::ACCOUNT_TAX_CODE => null,
                    ]
                ),
            ],
            'product is digital' => [
                'hasProduct' => true,
                'hasAccount' => true,
                'hasProductTaxCode' => true,
                'hasAccountTaxCode' => false,
                'isProductDigital' => true,
                'taxationAddress' => $taxationAddress,
                'expectedContext' => new \ArrayObject(
                    [
                        Taxable::DIGITAL_PRODUCT => true,
                        Taxable::PRODUCT_TAX_CODE => self::PRODUCT_TAX_CODE,
                        Taxable::ACCOUNT_TAX_CODE => null,
                    ]
                ),
            ],
            'product without product tax code' => [
                'hasProduct' => true,
                'hasAccount' => true,
                'hasProductTaxCode' => false,
                'hasAccountTaxCode' => false,
                'isProductDigital' => false,
                'taxationAddress' => $taxationAddress,
                'expectedContext' => new \ArrayObject(
                    [
                        Taxable::DIGITAL_PRODUCT => false,
                        Taxable::PRODUCT_TAX_CODE => null,
                        Taxable::ACCOUNT_TAX_CODE => null,
                    ]
                ),
            ],
            'nullable taxation address' => [
                'hasProduct' => true,
                'hasAccount' => true,
                'hasProductTaxCode' => true,
                'hasAccountTaxCode' => true,
                'isProductDigital' => true,
                'taxationAddress' => null,
                'expectedContext' => new \ArrayObject([
                    Taxable::DIGITAL_PRODUCT => false,
                    Taxable::PRODUCT_TAX_CODE => self::PRODUCT_TAX_CODE,
                    Taxable::ACCOUNT_TAX_CODE => self::ACCOUNT_TAX_CODE,
                ])
            ],
            'order with account and account product tax code' => [
                'hasProduct' => true,
                'hasAccount' => true,
                'hasProductTaxCode' => true,
                'hasAccountTaxCode' => true,
                'isProductDigital' => true,
                'taxationAddress' => $taxationAddress,
                'expectedContext' => new \ArrayObject(
                    [
                        Taxable::DIGITAL_PRODUCT => true,
                        Taxable::PRODUCT_TAX_CODE => self::PRODUCT_TAX_CODE,
                        Taxable::ACCOUNT_TAX_CODE => self::ACCOUNT_TAX_CODE,
                    ]
                ),
            ],
            'order with account and without account tax code' => [
                'hasProduct' => true,
                'hasAccount' => true,
                'hasProductTaxCode' => true,
                'hasAccountTaxCode' => false,
                'isProductDigital' => true,
                'taxationAddress' => $taxationAddress,
                'expectedContext' => new \ArrayObject(
                    [
                        Taxable::DIGITAL_PRODUCT => true,
                        Taxable::PRODUCT_TAX_CODE => self::PRODUCT_TAX_CODE,
                        Taxable::ACCOUNT_TAX_CODE => null,
                    ]
                ),
            ],
            'order without account' => [
                'hasProduct' => true,
                'hasAccount' => false,
                'hasProductTaxCode' => true,
                'hasAccountTaxCode' => false,
                'isProductDigital' => true,
                'taxationAddress' => $taxationAddress,
                'expectedContext' => new \ArrayObject(
                    [
                        Taxable::DIGITAL_PRODUCT => true,
                        Taxable::PRODUCT_TAX_CODE => self::PRODUCT_TAX_CODE,
                        Taxable::ACCOUNT_TAX_CODE => null,
                    ]
                ),
            ],
            'order with account Group tax code' => [
                'hasProduct' => true,
                'hasAccount' => true,
                'hasProductTaxCode' => true,
                'hasAccountTaxCode' => false,
                'isProductDigital' => true,
                'taxationAddress' => $taxationAddress,
                'expectedContext' => new \ArrayObject(
                    [
                        Taxable::DIGITAL_PRODUCT => true,
                        Taxable::PRODUCT_TAX_CODE => self::PRODUCT_TAX_CODE,
                        Taxable::ACCOUNT_TAX_CODE => self::ACCOUNT_GROUP_TAX_CODE,
                    ]
                ),
                'hasAccountGroup' => true,
                'hasAccountGroupTaxCode' => true
            ],
            'order without account Group tax code and account tax code' => [
                'hasProduct' => true,
                'hasAccount' => true,
                'hasProductTaxCode' => true,
                'hasAccountTaxCode' => true,
                'isProductDigital' => true,
                'taxationAddress' => $taxationAddress,
                'expectedContext' => new \ArrayObject(
                    [
                        Taxable::DIGITAL_PRODUCT => true,
                        Taxable::PRODUCT_TAX_CODE => self::PRODUCT_TAX_CODE,
                        Taxable::ACCOUNT_TAX_CODE => self::ACCOUNT_TAX_CODE,
                    ]
                ),
                'hasAccountGroup' => true,
                'hasAccountGroupTaxCode' => true
            ],
        ];
    }

    public function testIncorrectOrderLineItemClass()
    {
        $object = new \stdClass();
        $contextEvent = new ContextEvent($object);
        $this->handler->onContextEvent($contextEvent);

        $this->assertSame($object, $contextEvent->getMappingObject());
        $this->assertEmpty($contextEvent->getContext());
    }
}
