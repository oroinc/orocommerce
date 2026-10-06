<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Model\Condition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Model\Condition\GuestQuoteAccessible;
use Oro\Bundle\SaleBundle\Provider\GuestQuoteAccessProviderInterface;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\PropertyAccess\PropertyPath;

class GuestQuoteAccessibleTest extends \PHPUnit\Framework\TestCase
{
    /** @var GuestQuoteAccessProviderInterface|MockObject */
    private $guestQuoteAccessProvider;

    /** @var GuestQuoteAccessible */
    private $condition;

    protected function setUp(): void
    {
        $this->guestQuoteAccessProvider = $this->createMock(GuestQuoteAccessProviderInterface::class);

        $this->condition = new GuestQuoteAccessible($this->guestQuoteAccessProvider);
        $this->condition->setContextAccessor(new ContextAccessor());
    }

    public function testGetName()
    {
        self::assertEquals(GuestQuoteAccessible::NAME, $this->condition->getName());
    }

    public function testInitializeException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('First option should be valid property definition.');

        $this->condition->initialize([]);
    }

    public function testEvaluateWhenGuestAccessGranted()
    {
        $quote = new Quote();

        $this->guestQuoteAccessProvider->expects(self::once())
            ->method('isGranted')
            ->with($quote)
            ->willReturn(true);

        self::assertSame($this->condition, $this->condition->initialize([new PropertyPath('quote')]));
        self::assertTrue($this->condition->evaluate(['quote' => $quote], new ArrayCollection()));
    }

    public function testEvaluateWhenGuestAccessNotGranted()
    {
        $quote = new Quote();

        $this->guestQuoteAccessProvider->expects(self::once())
            ->method('isGranted')
            ->with($quote)
            ->willReturn(false);

        $this->condition->initialize([new PropertyPath('quote')]);

        self::assertFalse($this->condition->evaluate(['quote' => $quote], new ArrayCollection()));
    }

    /**
     * @dataProvider notQuoteDataProvider
     */
    public function testEvaluateWhenNotQuote(?object $quote)
    {
        $this->guestQuoteAccessProvider->expects(self::never())
            ->method('isGranted');

        $this->condition->initialize([new PropertyPath('quote')]);

        self::assertFalse($this->condition->evaluate(['quote' => $quote], new ArrayCollection()));
    }

    public function notQuoteDataProvider(): array
    {
        return [
            'no quote' => [null],
            'quote demand' => [new QuoteDemand()],
        ];
    }
}
