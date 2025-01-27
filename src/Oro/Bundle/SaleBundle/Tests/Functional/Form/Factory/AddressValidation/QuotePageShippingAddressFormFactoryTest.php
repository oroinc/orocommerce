<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\Form\Factory\AddressValidation;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUser;
use Oro\Bundle\SaleBundle\Entity\QuoteAddress;
use Oro\Bundle\SaleBundle\Form\Factory\AddressValidation\QuotePageShippingAddressFormFactory;
use Oro\Bundle\SaleBundle\Form\Type\QuoteAddressType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class QuotePageShippingAddressFormFactoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->loadFixtures([
            LoadCustomerUser::class,
        ]);
    }

    public function testThatShippingAddressFormReturned(): void
    {
        $this->updateUserSecurityToken(self::AUTH_USER);

        /** @var QuotePageShippingAddressFormFactory $quoteAddressFormFactory */
        $quoteAddressFormFactory = self::getContainer()->get(
            'oro_sale.form.factory.address_validation.address_form.quote_page.shipping_address'
        );

        /** @var RequestStack $requestStack */
        $requestStack = self::getContainer()->get('request_stack');

        $customerUser = $this->getReference(LoadCustomerUser::CUSTOMER_USER);
        $customerUserId = $customerUser->getId();
        $customerId = $customerUser->getCustomer()->getId();

        $request = new Request();
        $request->request->set('oro_sale_quote', [
            'customer' => $customerId,
            'customerUser' => $customerUserId,
        ]);

        $requestStack->push($request);

        $addressForm = $quoteAddressFormFactory->createAddressForm($request);

        self::assertEquals('shippingAddress', $addressForm->getName());
        self::assertInstanceOf(QuoteAddressType::class, $addressForm->getConfig()->getType()->getInnerType());
    }

    public function testThatShippingAddressFormReturnedWhenHasExplicitAddress(): void
    {
        $this->updateUserSecurityToken(self::AUTH_USER);

        /** @var QuotePageShippingAddressFormFactory $quoteAddressFormFactory */
        $quoteAddressFormFactory = self::getContainer()->get(
            'oro_sale.form.factory.address_validation.address_form.quote_page.shipping_address'
        );

        /** @var RequestStack $requestStack */
        $requestStack = self::getContainer()->get('request_stack');

        $customerUser = $this->getReference(LoadCustomerUser::CUSTOMER_USER);
        $customerUserId = $customerUser->getId();
        $customerId = $customerUser->getCustomer()->getId();
        $quoteAddress = new QuoteAddress();

        $request = new Request();
        $request->request->set('oro_sale_quote', [
            'customer' => $customerId,
            'customerUser' => $customerUserId,
        ]);

        $requestStack->push($request);

        $addressForm = $quoteAddressFormFactory->createAddressForm($request, $quoteAddress);

        self::assertEquals('shippingAddress', $addressForm->getName());
        self::assertInstanceOf(QuoteAddressType::class, $addressForm->getConfig()->getType()->getInnerType());
        self::assertSame($quoteAddress, $addressForm->getData());
    }
}
