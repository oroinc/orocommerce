<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Form\Factory\AddressValidation;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUser;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Form\Factory\AddressValidation\OrderPageAddressFormFactory;
use Oro\Bundle\OrderBundle\Form\Type\OrderAddressType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class OrderPageAddressFormFactoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([
            LoadCustomerUser::class,
        ]);

        $this->updateUserSecurityToken(self::AUTH_USER);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testThatAddressFormReturned(string $fqcnFactory, string $addressType): void
    {
        /**
         * @var OrderPageAddressFormFactory $addressFormFactory
         */
        $addressFormFactory = self::getContainer()->get($fqcnFactory);

        /**
         * @var RequestStack $requestStack
         */
        $requestStack = self::getContainer()->get('request_stack');

        $customerUser = $this->getReference(LoadCustomerUser::CUSTOMER_USER);
        $customerUserId = $customerUser->getId();
        $customerId = $customerUser->getCustomer()->getId();

        $request = new Request();
        $request->request->set('oro_order_type', [
            'customer' => $customerId,
            'customerUser' => $customerUserId
        ]);

        $requestStack->push($request);

        $addressForm = $addressFormFactory->createAddressForm($request);

        self::assertEquals($addressType, $addressForm->getConfig()->getOption('address_type'));
        self::assertInstanceOf(OrderAddressType::class, $addressForm->getConfig()->getType()->getInnerType());
    }

    private static function dataProvider(): array
    {
        return [
            [
                'oro_order.form.factory.address_validation.address_form.order_page.billing_address',
                'billing'
            ],
            [
                'oro_order.form.factory.address_validation.address_form.order_page.shipping_address',
                'shipping'
            ]
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testThatAddressFormReturnedWhenHasExplicitAddress(string $fqcnFactory, string $addressType): void
    {
        /** @var OrderPageAddressFormFactory $addressFormFactory */
        $addressFormFactory = self::getContainer()->get($fqcnFactory);

        /** @var RequestStack $requestStack */
        $requestStack = self::getContainer()->get('request_stack');

        $customerUser = $this->getReference(LoadCustomerUser::CUSTOMER_USER);
        $customerUserId = $customerUser->getId();
        $customerId = $customerUser->getCustomer()->getId();
        $orderAddress = new OrderAddress();

        $request = new Request();
        $request->request->set('oro_order_type', [
            'customer' => $customerId,
            'customerUser' => $customerUserId
        ]);

        $requestStack->push($request);

        $addressForm = $addressFormFactory->createAddressForm($request, $orderAddress);

        self::assertEquals($addressType, $addressForm->getConfig()->getOption('address_type'));
        self::assertInstanceOf(OrderAddressType::class, $addressForm->getConfig()->getType()->getInnerType());
        self::assertSame($orderAddress, $addressForm->getData());
    }
}
