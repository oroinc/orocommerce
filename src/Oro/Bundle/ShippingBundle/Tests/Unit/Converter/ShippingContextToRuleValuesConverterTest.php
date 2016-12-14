<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Converter;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Converter\ShippingContextToRuleValuesConverter;
use Oro\Bundle\ShippingBundle\ExpressionLanguage\LineItemDecoratorFactory;
use Oro\Bundle\ShippingBundle\QueryDesigner\SelectQueryConverter;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingAddressStub;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class ShippingContextToRuleValuesConverterTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var EntityFieldProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldProvider;

    /**
     * @var SelectQueryConverter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $converter;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrine;

    /**
     * @var FieldHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldHelper;

    /**
     * @var LineItemDecoratorFactory
     */
    protected $factory;

    /**
     * @var ShippingContextToRuleValuesConverter
     */
    protected $shippingContextToRuleValuesConverter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->converter = $this->getMockBuilder(SelectQueryConverter::class)
            ->disableOriginalConstructor()->getMock();

        $this->doctrine = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();

        $this->fieldProvider = $this->getMockBuilder(EntityFieldProvider::class)
            ->disableOriginalConstructor()->getMock();

        $this->fieldHelper = $this->getMockBuilder(FieldHelper::class)
            ->disableOriginalConstructor()->getMock();

        $this->factory = new LineItemDecoratorFactory(
            $this->fieldProvider,
            $this->converter,
            $this->doctrine,
            $this->fieldHelper
        );

        $this->shippingContextToRuleValuesConverter = new ShippingContextToRuleValuesConverter(
            $this->factory
        );
    }

    public function testConvert()
    {
        $contextData = $this->getShippingContextData();
        $context = $this->getShippingContext($contextData);
        $expected = $contextData;
        $expected['lineItems'] = array_map(function (ShippingLineItem $lineItem) use ($expected) {
            $lineItem->setProductHolder($lineItem);
            return $this->factory->createOrderLineItemDecorator($expected['lineItems'], $lineItem);
        }, $expected['lineItems']);

        $this->assertEquals($expected, $this->shippingContextToRuleValuesConverter->convert($context));
    }

    /**
     * @param array $contextData
     * @return ShippingContextInterface
     */
    protected function getShippingContext($contextData)
    {
        return $this->getEntity(ShippingContext::class, $contextData);
    }
    /**
     * @return array
     */
    protected function getShippingContextData()
    {
        return [
            'lineItems' => [$this->getEntity(
                ShippingLineItem::class,
                ['product' => $this->getEntity(Product::class, ['id' => 1])]
            )],
            'shippingOrigin' => $this->getEntity(ShippingAddressStub::class, [
                'region' => $this->getEntity(Region::class, [
                    'code' => 'CA',
                ]),
            ]),
            'billingAddress' => $this->getEntity(ShippingAddressStub::class, [
                'country' => new Country('US'),
            ]),
            'shippingAddress' => $this->getEntity(ShippingAddressStub::class, [
                'country' => new Country('US'),
                'region' => $this->getEntity(Region::class, [
                    'combinedCode' => 'US-CA',
                    'code' => 'CA',
                ]),
                'postalCode' => '90401',
            ]),
            'paymentMethod' => 'integration_payment_method',
            'currency' => 'USD',
            'subtotal' => Price::create(10.0, 'USD'),
            'customer' => (new Account())->setName('Customer Name'),
            'customerUser' => (new AccountUser())->setFirstName('First Name'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset(
            $this->converter,
            $this->doctrine,
            $this->fieldProvider,
            $this->fieldHelper,
            $this->factory,
            $this->shippingContextToRuleValuesConverter
        );
    }
}
