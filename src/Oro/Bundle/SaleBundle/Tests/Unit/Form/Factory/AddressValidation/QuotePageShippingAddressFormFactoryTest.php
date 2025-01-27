<?php

declare(strict_types=1);

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\Factory\AddressValidation;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteAddress;
use Oro\Bundle\SaleBundle\Form\Factory\AddressValidation\QuotePageShippingAddressFormFactory;
use Oro\Bundle\SaleBundle\Form\Type\QuoteType;
use Oro\Bundle\SaleBundle\Model\QuoteRequestHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

final class QuotePageShippingAddressFormFactoryTest extends TestCase
{
    private FormFactoryInterface|MockObject $formFactory;

    private QuoteRequestHandler|MockObject $quoteRequestHandler;

    private QuotePageShippingAddressFormFactory $addressFormFactory;

    #[\Override]
    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->quoteRequestHandler = $this->createMock(QuoteRequestHandler::class);

        $this->addressFormFactory = new QuotePageShippingAddressFormFactory(
            $this->formFactory,
            $this->quoteRequestHandler
        );
    }

    public function testCreateAddressForm(): void
    {
        $request = new Request();
        $quoteForm = $this->createMock(FormInterface::class);
        $addressForm = $this->createMock(FormInterface::class);

        $customer = new Customer();
        $this->quoteRequestHandler
            ->expects(self::once())
            ->method('getCustomer')
            ->willReturn($customer);

        $customerUser = new CustomerUser();
        $this->quoteRequestHandler
            ->expects(self::once())
            ->method('getCustomerUser')
            ->willReturn($customerUser);

        $quoteForm
            ->expects(self::once())
            ->method('get')
            ->with('shippingAddress')
            ->willReturn($addressForm);

        $this->formFactory
            ->expects(self::once())
            ->method('create')
            ->with(
                QuoteType::class,
                self::callback(static function (Quote $quote) use ($customer, $customerUser) {
                    self::assertSame($customer, $quote->getCustomer());
                    self::assertSame($customerUser, $quote->getCustomerUser());

                    return true;
                })
            )
            ->willReturn($quoteForm);

        $result = $this->addressFormFactory->createAddressForm($request);

        self::assertSame($addressForm, $result);
    }

    public function testCreateAddressFormWithExplicitAddress(): void
    {
        $request = new Request();
        $address = new QuoteAddress();
        $quoteForm = $this->createMock(FormInterface::class);
        $addressForm = $this->createMock(FormInterface::class);

        $customer = new Customer();
        $this->quoteRequestHandler
            ->expects(self::once())
            ->method('getCustomer')
            ->willReturn($customer);

        $customerUser = new CustomerUser();
        $this->quoteRequestHandler
            ->expects(self::once())
            ->method('getCustomerUser')
            ->willReturn($customerUser);

        $quoteForm
            ->expects(self::once())
            ->method('get')
            ->with('shippingAddress')
            ->willReturn($addressForm);

        $this->formFactory
            ->expects(self::once())
            ->method('create')
            ->with(
                QuoteType::class,
                self::callback(static function (Quote $quote) use ($customer, $customerUser, $address) {
                    self::assertSame($customer, $quote->getCustomer());
                    self::assertSame($customerUser, $quote->getCustomerUser());
                    self::assertSame($address, $quote->getShippingAddress());

                    return true;
                })
            )
            ->willReturn($quoteForm);

        $result = $this->addressFormFactory->createAddressForm($request, $address);

        self::assertSame($addressForm, $result);
    }
}
