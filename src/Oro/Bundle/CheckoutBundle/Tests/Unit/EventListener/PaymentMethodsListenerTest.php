<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\EventListener\PaymentMethodsListener;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutPaymentContextFactory;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\CheckoutPaymentContextProvider;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Provider\MethodsConfigsRule\Context\MethodsConfigsRulesByContextProviderInterface;

class PaymentMethodsListenerTest extends AbstractMethodsListenerTest
{
    /**
     * @var MethodsConfigsRulesByContextProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configsRuleProvider;

    /**
     * @var CheckoutPaymentContextFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $contextFactory;

    /**
     * @var CheckoutPaymentContextProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $checkoutContextProvider;

    protected function setUp()
    {
        parent::setUp();

        $this->configsRuleProvider = $this->createMock(MethodsConfigsRulesByContextProviderInterface::class);

        $this->contextFactory = $this->createMock(CheckoutPaymentContextFactory::class);
        $this->checkoutContextProvider = $this->createMock(CheckoutPaymentContextProvider::class);

        $this->listener = new PaymentMethodsListener(
            $this->addressProvider,
            $this->orderAddressSecurityProvider,
            $this->orderAddressManager,
            $this->configsRuleProvider,
            $this->contextFactory
        );
    }

    /**
     * @return array
     */
    public function manualEditGrantedDataProvider()
    {
        return [
            'manual edit granted and no configs returned' => [
                'shippingManualEdit' => null,
                'billingManualEdit' => true,
                'methodConfigs' => [],
            ],
            'manual edit granted and method configs returned' => [
                'shippingManualEdit' => null,
                'billingManualEdit' => true,
                'methodConfigs' => [
                    $this->getEntity(PaymentMethodsConfigsRule::class, ['id' => 1]),
                    $this->getEntity(PaymentMethodsConfigsRule::class, ['id' => 2]),
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function notManualEditDataProvider()
    {
        $customer = $this->getEntity(Customer::class);
        $customerUser = $this->getEntity(CustomerUser::class);
        $checkout = $this->getEntity(Checkout::class, [
            'customer' => $customer,
            'customerUser' => $customerUser,
        ]);

        $billingCustomerAddress = $this->getEntity(OrderAddress::class, ['id' => 2]);
        $billingCustomerUserAddress = $this->getEntity(OrderAddress::class, ['id' => 4]);

        return [
            'error because no configs for customer addresses in provider' => [
                'checkout' => $checkout,
                'customerAddressesMap' => [
                    [$customer, AddressType::TYPE_BILLING, [$billingCustomerAddress]],
                ],
                'customerUserAddressesMap' => [
                    [$customerUser, AddressType::TYPE_BILLING, [$billingCustomerUserAddress]],
                ],
                'consecutiveAddresses' => [
                    [$billingCustomerAddress],
                    [$billingCustomerUserAddress],
                ],
                'expectedCalls' => 2,
                'onConsecutiveMethodConfigs' => [[], []],
            ],
            'no error because has configs for customer addresses in provider' => [
                'checkout' => $checkout,
                'customerAddressesMap' => [
                    [$customer, AddressType::TYPE_BILLING, [$billingCustomerAddress]],
                ],
                'customerUserAddressesMap' => [
                    [$customerUser, AddressType::TYPE_BILLING, []],
                ],
                'consecutiveAddresses' => [[$billingCustomerAddress]],
                'expectedCalls' => 1,
                'onConsecutiveMethodConfigs' => [
                    [$this->getEntity(PaymentMethodsConfigsRule::class, ['id' => 1])],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        return $this->createMock(PaymentContextInterface::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function getConfigRuleProviderMethod()
    {
        return 'getPaymentMethodsConfigsRules';
    }

    /**
     * {@inheritdoc}
     */
    protected function getAddressToCheck(Checkout $checkout)
    {
        return $checkout->getBillingAddress();
    }
}
