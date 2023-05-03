<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Mapper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Mapper\OrderMapper;
use Oro\Bundle\CheckoutBundle\Tests\Unit\Model\Action\CheckoutSourceStub;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;

class OrderMapperTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var FieldHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldHelper;

    /** @var PaymentTermAssociationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentTermAssociationProvider;

    /** @var OrderMapper */
    private $mapper;

    protected function setUp(): void
    {
        $this->fieldHelper = $this->createMock(FieldHelper::class);
        $this->paymentTermAssociationProvider = $this->createMock(PaymentTermAssociationProvider::class);

        $this->mapper = new OrderMapper(
            $this->fieldHelper,
            PropertyAccess::createPropertyAccessor(),
            $this->paymentTermAssociationProvider
        );
    }

    public function testMap(): void
    {
        $this->fieldHelper->expects(self::once())
            ->method('getEntityFields')
            ->with(
                Order::class,
                EntityFieldProvider::OPTION_WITH_RELATIONS
                | EntityFieldProvider::OPTION_WITH_UNIDIRECTIONAL
                | EntityFieldProvider::OPTION_APPLY_EXCLUSIONS
            )
            ->willReturn(
                [
                    ['name' => 'id', 'identifier' => true],
                    ['name' => 'website'],
                    ['name' => 'paymentTerm'],
                    ['name' => 'shippingAddress'],
                    ['name' => 'billingAddress'],
                    ['name' => 'currency'],
                    ['name' => 'someRelationEntity:someRelationField'],
                ]
            );

        $website = new Website();
        $address = new OrderAddress();
        $address->setLabel('address1');
        $shippingCost = Price::create(10, 'USD');
        $checkout = (new Checkout())
            ->setOrganization(new Organization())
            ->setWebsite($website)
            ->setShippingAddress($address)
            ->setBillingAddress($address)
            ->setShippingCost($shippingCost)
            ->setCurrency('USD');

        $newAddress = new OrderAddress();
        $newAddress->setLabel('address2');

        $paymentTerm = new PaymentTerm();
        $this->paymentTermAssociationProvider->expects(self::once())
            ->method('setPaymentTerm')
            ->with(self::isInstanceOf(Order::class), $paymentTerm);

        $data = [
            'shippingAddress' => $newAddress,
            'paymentTerm' => $paymentTerm,
            'skipMe' => true
        ];

        $order = $this->mapper->map($checkout, $data, ['skipMe' => true]);

        self::assertInstanceOf(Order::class, $order);
        self::assertEquals($address, $order->getBillingAddress());
        self::assertEquals($newAddress, $order->getShippingAddress());
        self::assertEquals($website, $order->getWebsite());
        self::assertEquals($shippingCost, $order->getShippingCost());
    }

    public function testMapWithSourceEntity(): void
    {
        $this->fieldHelper->expects(self::once())
            ->method('getEntityFields')
            ->with(
                Order::class,
                EntityFieldProvider::OPTION_WITH_RELATIONS
                | EntityFieldProvider::OPTION_WITH_UNIDIRECTIONAL
                | EntityFieldProvider::OPTION_APPLY_EXCLUSIONS
            )
            ->willReturn([]);

        $source = new CheckoutSourceStub();
        $source->setId(2);
        $source->setShoppingList($this->getEntity(ShoppingList::class, ['id' => 5, 'label' => 'SL#1']));
        $checkout = (new Checkout())->setSource($source);

        $order = $this->mapper->map($checkout, []);

        self::assertInstanceOf(Order::class, $order);
        self::assertEquals(ShoppingList::class, $order->getSourceEntityClass());
        self::assertEquals(5, $order->getSourceEntityId());
        self::assertEquals('SL#1', $order->getSourceEntityIdentifier());
    }

    public function testMapIdsIgnored(): void
    {
        $this->fieldHelper->expects(self::once())
            ->method('getEntityFields')
            ->with(
                Order::class,
                EntityFieldProvider::OPTION_WITH_RELATIONS
                | EntityFieldProvider::OPTION_WITH_UNIDIRECTIONAL
                | EntityFieldProvider::OPTION_APPLY_EXCLUSIONS
            )
            ->willReturn(
                [['name' => 'id', 'identifier' => true]]
            );

        $checkout = $this->getEntity(Checkout::class, ['id' => 5]);

        $order = $this->mapper->map($checkout, []);

        self::assertInstanceOf(Order::class, $order);
        self::assertNull($order->getId());
    }
}
