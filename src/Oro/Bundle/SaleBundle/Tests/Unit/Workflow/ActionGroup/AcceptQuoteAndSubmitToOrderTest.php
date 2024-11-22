<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Workflow\ActionGroup;

use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutLineItemsProvider;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\StartCheckoutInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteAddress;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProductDemand;
use Oro\Bundle\SaleBundle\Quote\Demand\Subtotals\Calculator\QuoteDemandSubtotalsCalculatorInterface;
use Oro\Bundle\SaleBundle\Workflow\ActionGroup\AcceptQuoteAndSubmitToOrder;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AcceptQuoteAndSubmitToOrderTest extends TestCase
{
    use EntityTrait;

    private UrlGeneratorInterface|MockObject $urlGenerator;
    private QuoteDemandSubtotalsCalculatorInterface|MockObject $quoteDemandSubtotalsCalculator;
    private StartCheckoutInterface|MockObject $startCheckout;
    private CheckoutLineItemsProvider|MockObject $checkoutLineItemsProvider;
    private ActionExecutor|MockObject $actionExecutor;

    private AcceptQuoteAndSubmitToOrder $service;

    protected function setUp(): void
    {
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->quoteDemandSubtotalsCalculator = $this->createMock(QuoteDemandSubtotalsCalculatorInterface::class);
        $this->startCheckout = $this->createMock(StartCheckoutInterface::class);
        $this->checkoutLineItemsProvider = $this->createMock(CheckoutLineItemsProvider::class);
        $this->actionExecutor = $this->createMock(ActionExecutor::class);

        $this->service = new AcceptQuoteAndSubmitToOrder(
            $this->urlGenerator,
            $this->quoteDemandSubtotalsCalculator,
            $this->startCheckout,
            $this->checkoutLineItemsProvider,
            $this->actionExecutor
        );
    }

    /**
     * @dataProvider executeDataProvider
     */
    public function testExecute(?CustomerUser $customerUser, string $startTransition)
    {
        $quoteShippingAddress = $this->getQuoteAddress();
        $quote = $this->getEntity(Quote::class, ['id' => 1]);
        $quote->setCurrency('USD');
        $quote->setEstimatedShippingCostAmount(10.0);
        $quote->setShippingAddress($quoteShippingAddress);
        $quote->setShippingMethod('UPS');
        $quote->setShippingMethodType('Next Day Air');
        $quote->setShipUntil(new \DateTime('2024-12-31'));
        $quote->setPoNumber('PO123456');

        $quote->setCustomerUser($customerUser);

        $quoteDemand = $this->getEntity(QuoteDemand::class, ['id' => 123]);
        $quoteDemand->setQuote($quote);
        $quoteDemand->addDemandProduct($this->createMock(QuoteProductDemand::class));

        $checkout = new Checkout();
        $checkout->addLineItem(new CheckoutLineItem());

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with('oro_sale_quote_frontend_choice', ['id' => 123])
            ->willReturn('edit_link');

        $this->quoteDemandSubtotalsCalculator->expects($this->once())
            ->method('calculateSubtotals')
            ->with($quoteDemand);

        $this->checkoutLineItemsProvider->expects($this->once())
            ->method('getProductSkusWithDifferences')
            ->with($checkout->getLineItems(), $quoteDemand->getDemandProducts())
            ->willReturn(['SKU1', 'SKU3']);

        $this->actionExecutor->expects($this->once())
            ->method('executeAction')
            ->with(
                'flash_message',
                [
                    'message' => 'oro.checkout.frontend.checkout.some_changes_in_line_items',
                    'message_parameters' => [
                        'skus' => 'SKU1, SKU3'
                    ],
                    'type' => 'warning'
                ]
            );

        $this->startCheckout->expects($this->once())
            ->method('execute')
            ->with(
                ['quoteDemand' => $quoteDemand],
                true,
                [
                    'shippingCost' => Price::create(10.0, 'USD'),
                    'shippingMethod' => 'UPS',
                    'shippingMethodType' => 'Next Day Air',
                    'shipUntil' => $quote->getShipUntil(),
                    'poNumber' => 'PO123456',
                    'shippingAddress' => $this->createOrderAddressByQuoteAddress($quoteShippingAddress)
                ],
                [
                    'allow_manual_source_remove' => false,
                    'auto_remove_source' => false,
                    'edit_order_link' => 'edit_link',
                    'disallow_shipping_address_edit' => true,
                    'disallow_shipping_method_edit' => true
                ],
                true,
                false,
                $startTransition
            )
            ->willReturn(['checkout' => $checkout]);

        $result = $this->service->execute($quoteDemand);
        $this->assertArrayHasKey('checkout', $result);
    }

    public static function executeDataProvider(): array
    {
        return [
            [null, 'start_from_quote_as_guest'],
            [(new CustomerUser())->setIsGuest(true), 'start_from_quote_as_guest'],
            [(new CustomerUser())->setIsGuest(false), 'start_from_quote'],
        ];
    }

    public function testExecuteWithoutShippingAddressAndDiffProducts()
    {
        $quote = $this->getEntity(Quote::class, ['id' => 1]);
        $quote->setCurrency('USD');
        $quote->setEstimatedShippingCostAmount(10.0);
        $quote->setShippingMethod('UPS');
        $quote->setShippingMethodType('Next Day Air');
        $quote->setShipUntil(new \DateTime('2024-12-31'));
        $quote->setPoNumber('PO123456');

        $customerUser = new CustomerUser();
        $customerUser->setIsGuest(false);
        $quote->setCustomerUser($customerUser);

        $quoteDemand = $this->getEntity(QuoteDemand::class, ['id' => 123]);
        $quoteDemand->setQuote($quote);
        $quoteDemand->addDemandProduct($this->createMock(QuoteProductDemand::class));

        $checkout = new Checkout();
        $checkout->addLineItem(new CheckoutLineItem());

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with('oro_sale_quote_frontend_choice', ['id' => 123])
            ->willReturn('edit_link');

        $this->quoteDemandSubtotalsCalculator->expects($this->once())
            ->method('calculateSubtotals')
            ->with($quoteDemand);

        $this->checkoutLineItemsProvider->expects($this->once())
            ->method('getProductSkusWithDifferences')
            ->with($checkout->getLineItems(), $quoteDemand->getDemandProducts())
            ->willReturn([]);

        $this->actionExecutor->expects($this->never())
            ->method('executeAction');

        $this->startCheckout->expects($this->once())
            ->method('execute')
            ->with(
                ['quoteDemand' => $quoteDemand],
                true,
                [
                    'shippingCost' => Price::create(10.0, 'USD'),
                    'shippingMethod' => 'UPS',
                    'shippingMethodType' => 'Next Day Air',
                    'shipUntil' => $quote->getShipUntil(),
                    'poNumber' => 'PO123456'
                ],
                [
                    'allow_manual_source_remove' => false,
                    'auto_remove_source' => false,
                    'edit_order_link' => 'edit_link',
                    'disallow_shipping_address_edit' => false,
                    'disallow_shipping_method_edit' => true
                ],
                true,
                false,
                'start_from_quote'
            )
            ->willReturn(['checkout' => $checkout]);

        $result = $this->service->execute($quoteDemand);
        $this->assertArrayHasKey('checkout', $result);
    }

    private function getQuoteAddress(): QuoteAddress
    {
        $address = new QuoteAddress();
        $address->setLabel('Test Label');
        $address->setOrganization('Test Organization');
        $address->setStreet('123 Test St');
        $address->setStreet2('Apt 456');
        $address->setCity('Test City');
        $address->setPostalCode('12345');
        $address->setCountry(new Country('US'));
        $address->setRegion(new Region('US-CA'));
        $address->setRegionText('Test Region Text');
        $address->setNamePrefix('Mr.');
        $address->setFirstName('John');
        $address->setMiddleName('M.');
        $address->setLastName('Doe');
        $address->setNameSuffix('Jr.');
        $address->setPhone('555-1234');

        return $address;
    }

    private function createOrderAddressByQuoteAddress(QuoteAddress $quoteAddress): OrderAddress
    {
        $orderAddress = new OrderAddress();
        $orderAddress->setLabel($quoteAddress->getLabel());
        $orderAddress->setOrganization($quoteAddress->getOrganization());
        $orderAddress->setStreet($quoteAddress->getStreet());
        $orderAddress->setStreet2($quoteAddress->getStreet2());
        $orderAddress->setCity($quoteAddress->getCity());
        $orderAddress->setPostalCode($quoteAddress->getPostalCode());
        $orderAddress->setCountry($quoteAddress->getCountry());
        $orderAddress->setRegion($quoteAddress->getRegion());
        $orderAddress->setRegionText($quoteAddress->getRegionText());
        $orderAddress->setNamePrefix($quoteAddress->getNamePrefix());
        $orderAddress->setFirstName($quoteAddress->getFirstName());
        $orderAddress->setMiddleName($quoteAddress->getMiddleName());
        $orderAddress->setLastName($quoteAddress->getLastName());
        $orderAddress->setNameSuffix($quoteAddress->getNameSuffix());
        $orderAddress->setPhone($quoteAddress->getPhone());
        $orderAddress->setFromExternalSource(true);

        return $orderAddress;
    }
}
