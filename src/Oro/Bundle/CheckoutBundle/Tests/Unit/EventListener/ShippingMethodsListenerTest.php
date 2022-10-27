<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\EventListener\ShippingMethodsListener;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutShippingContextProvider;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Provider\MethodsConfigsRule\Context\MethodsConfigsRulesByContextProviderInterface;

class ShippingMethodsListenerTest extends AbstractMethodsListenerTest
{
    /**
     * @var MethodsConfigsRulesByContextProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configsRuleProvider;

    /**
     * @var CheckoutShippingContextProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $checkoutContextProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configsRuleProvider = $this->createMock(MethodsConfigsRulesByContextProviderInterface::class);

        $this->checkoutContextProvider = $this->getMockBuilder(CheckoutShippingContextProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new ShippingMethodsListener(
            $this->addressProvider,
            $this->orderAddressSecurityProvider,
            $this->orderAddressManager,
            $this->configsRuleProvider,
            $this->checkoutContextProvider
        );
    }

    protected function tearDown(): void
    {
        unset($this->listener, $this->checkoutContextProvider, $this->configsRuleProvider);

        parent::tearDown();
    }

    /**
     * {@inheritdoc}
     */
    public function manualEditGrantedDataProvider()
    {
        return [
            'shipping manual edit granted and no configs returned' => [
                'shippingManualEdit' => true,
                'billingManualEdit' => false,
                'methodConfigs' => []
            ],
            'billing manual edit granted and method configs returned' => [
                'shippingManualEdit' => false,
                'billingManualEdit' => true,
                'methodConfigs' => [
                    $this->getEntity(ShippingMethodsConfigsRule::class, ['id' => 1]),
                    $this->getEntity(ShippingMethodsConfigsRule::class, ['id' => 2])
                ]
            ],
            'shipping and billing manual edit granted and method config returned' => [
                'shippingManualEdit' => true,
                'billingManualEdit' => true,
                'methodConfigs' => [$this->getEntity(ShippingMethodsConfigsRule::class, ['id' => 1])]
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function notManualEditDataProvider()
    {
        $customer = $this->getEntity(Customer::class);
        $customerUser = $this->getEntity(CustomerUser::class);
        $checkout = $this->getEntity(Checkout::class, [
            'customer' => $customer,
            'customerUser' => $customerUser,
        ]);

        $shippingCustomerAddress = $this->getEntity(OrderAddress::class, ['id' => 1]);
        $shippingCustomerUserAddress = $this->getEntity(OrderAddress::class, ['id' => 3]);
        $billingCustomerAddress = $this->getEntity(OrderAddress::class, ['id' => 2]);
        $billingCustomerUserAddress = $this->getEntity(OrderAddress::class, ['id' => 4]);

        return [
            'has error because no configs for customer addresses in provider' => [
                'checkout' => $checkout,
                'customerAddressesMap' => [
                    [$customer, AddressType::TYPE_SHIPPING, [$shippingCustomerAddress]],
                    [$customer, AddressType::TYPE_BILLING, [$billingCustomerAddress]]
                ],
                'customerUserAddressesMap' => [
                    [$customerUser, AddressType::TYPE_SHIPPING, [$shippingCustomerUserAddress]],
                    [$customerUser, AddressType::TYPE_BILLING, [$billingCustomerUserAddress]]
                ],
                'consecutiveAddresses' => [
                    [$shippingCustomerAddress],
                    [$shippingCustomerUserAddress],
                    [$billingCustomerAddress],
                    [$billingCustomerUserAddress]
                ],
                'expectedCalls' => 4,
                'onConsecutiveMethodConfigs' => [[], [], [], []]
            ],
            'no error because has configs for customer addresses in provider' => [
                'checkout' => $checkout,
                'customerAddressesMap' => [
                    [$customer, AddressType::TYPE_SHIPPING, []],
                    [$customer, AddressType::TYPE_BILLING, [$billingCustomerAddress]]
                ],
                'customerUserAddressesMap' => [
                    [$customerUser, AddressType::TYPE_SHIPPING, [$shippingCustomerUserAddress]],
                    [$customerUser, AddressType::TYPE_BILLING, []]
                ],
                'consecutiveAddresses' => [[$shippingCustomerUserAddress], [$billingCustomerAddress]],
                'expectedCalls' => 2,
                'onConsecutiveMethodConfigs' => [
                    [],
                    [$this->getEntity(ShippingMethodsConfigsRule::class, ['id' => 1])]
                ]
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        return $this->createMock(ShippingContextInterface::class);
    }

    /**
     * @return string
     */
    protected function getConfigRuleProviderMethod()
    {
        return 'getShippingMethodsConfigsRules';
    }

    protected function getAddressToCheck(Checkout $checkout)
    {
        return $checkout->getShippingAddress();
    }
}
