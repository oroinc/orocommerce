<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Validator;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\InventoryBundle\Validator\UpcomingLabelCheckoutLineItemValidator;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Contracts\Translation\TranslatorInterface;

class UpcomingLabelCheckoutLineItemValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var UpcomingProductProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $provider;

    /** @var DateTimeFormatterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $dateFormatter;

    /** @var UpcomingLabelCheckoutLineItemValidator */
    private $validator;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->provider = $this->createMock(UpcomingProductProvider::class);
        $this->dateFormatter = $this->createMock(DateTimeFormatterInterface::class);

        $this->validator = new UpcomingLabelCheckoutLineItemValidator(
            $this->provider,
            $this->translator,
            $this->dateFormatter
        );
    }

    public function testValidateLineItemWithUpcomingProductWithoutDate()
    {
        $product = $this->createMock(Product::class);

        $lineItem = $this->createMock(CheckoutLineItem::class);
        $lineItem->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);

        $this->provider->expects($this->once())
            ->method('isUpcoming')
            ->with($product)
            ->willReturn(true);
        $this->provider->expects($this->once())
            ->method('getAvailabilityDate')
            ->with($product)
            ->willReturn(null);

        $message = 'This product will be available later';
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.inventory.is_upcoming.notification')
            ->willReturn($message);

        $this->assertSame($message, $this->validator->getMessageIfLineItemUpcoming($lineItem));
    }

    public function testValidateLineItemWithUpcomingProductWithDate()
    {
        $product = $this->createMock(Product::class);

        $lineItem = $this->createMock(CheckoutLineItem::class);
        $lineItem->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);

        $today = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->provider->expects($this->once())
            ->method('isUpcoming')
            ->with($product)
            ->willReturn(true);
        $this->provider->expects($this->once())
            ->method('getAvailabilityDate')
            ->with($product)
            ->willReturn($today);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.inventory.is_upcoming.notification_with_date')
            ->willReturn('This product will be available on 1/1/19');

        $this->dateFormatter->expects($this->once())
            ->method('formatDate')
            ->with($today)
            ->willReturn('1/1/19');

        $this->assertSame(
            'This product will be available on 1/1/19',
            $this->validator->getMessageIfLineItemUpcoming($lineItem)
        );
    }

    public function testValidateLineItemWithoutUpcomingProduct()
    {
        $product = $this->createMock(Product::class);

        $lineItem = $this->createMock(CheckoutLineItem::class);
        $lineItem->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);

        $this->provider->expects($this->once())
            ->method('isUpcoming')
            ->with($product)->willReturn(false);

        $this->assertNull($this->validator->getMessageIfLineItemUpcoming($lineItem));
    }
}
