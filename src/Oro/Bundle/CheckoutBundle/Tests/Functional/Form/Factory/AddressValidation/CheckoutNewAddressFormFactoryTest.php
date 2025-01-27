<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Form\Factory\AddressValidation;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Form\Factory\AddressValidation\CheckoutNewAddressFormFactory;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadCheckoutACLData;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;
use Symfony\Component\HttpFoundation\Request;

final class CheckoutNewAddressFormFactoryTest extends WebTestCase
{
    private Checkout $checkout;

    private Request $request;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        self::loadFixtures([
            LoadCheckoutACLData::class,
            LoadUserData::class,
        ]);

        $user = $this->getReference(LoadUserData::SIMPLE_USER);
        $this->updateUserSecurityToken($user->getEmail());

        $this->checkout = self::getReference(LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL);

        $requestStack = self::getContainer()->get('request_stack');

        $request = new Request();
        $request->attributes->set('checkout', $this->checkout);
        $request->attributes->set('_theme', 'default');

        $requestStack->push($request);

        $this->request = $request;
    }

    public function testThatBillingAddressFormCreated(): void
    {
        /** @var CheckoutNewAddressFormFactory $factory */
        $factory = self::getContainer()->get(
            'oro_checkout.form.factory.address_validation.single_page_address_form.new_billing_address'
        );

        $addressForm = $factory->createAddressForm($this->request);

        self::assertEquals('billing', $addressForm->getConfig()->getOption('addressType'));
        self::assertNotSame(
            $this->checkout,
            $addressForm->getRoot()->getData(),
            'Address form root data must not affect the original checkout'
        );
    }

    public function testThatBillingAddressFormCreatedWhenHasExplicitAddress(): void
    {
        /** @var CheckoutNewAddressFormFactory $factory */
        $factory = self::getContainer()->get(
            'oro_checkout.form.factory.address_validation.single_page_address_form.new_billing_address'
        );

        $billingAddress = new OrderAddress();
        $this->checkout->setBillingAddress($billingAddress);

        $newBillingAddress = new OrderAddress();
        $addressForm = $factory->createAddressForm($this->request, $newBillingAddress);

        self::assertEquals('billing', $addressForm->getConfig()->getOption('addressType'));
        self::assertSame($newBillingAddress, $addressForm->getData());
    }

    public function testThatShippingAddressFormCreated(): void
    {
        /** @var CheckoutNewAddressFormFactory $factory */
        $factory = self::getContainer()->get(
            'oro_checkout.form.factory.address_validation.single_page_address_form.new_shipping_address'
        );

        $addressForm = $factory->createAddressForm($this->request);

        self::assertEquals('shipping', $addressForm->getConfig()->getOption('addressType'));
        self::assertNotSame(
            $this->checkout,
            $addressForm->getRoot()->getData(),
            'Address form root data must not affect the original checkout'
        );
    }

    public function testThatShippingAddressFormCreatedWhenHasExplicitAddress(): void
    {
        /** @var CheckoutNewAddressFormFactory $factory */
        $factory = self::getContainer()->get(
            'oro_checkout.form.factory.address_validation.single_page_address_form.new_shipping_address'
        );

        $shippingAddress = new OrderAddress();
        $this->checkout->setShippingAddress($shippingAddress);

        $newShippingAddress = new OrderAddress();
        $addressForm = $factory->createAddressForm($this->request, $newShippingAddress);

        self::assertEquals('shipping', $addressForm->getConfig()->getOption('addressType'));
        self::assertSame($newShippingAddress, $addressForm->getData());
    }
}
