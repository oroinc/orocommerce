<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Placeholder;

use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Placeholder\CurrencyPlaceholder;

class CurrencyPlaceholderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CurrencyPlaceholder
     */
    private $placeholder;

    /**
     * @var UserCurrencyManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $currencyManager;

    protected function setUp(): void
    {
        $this->currencyManager = $this->getMockBuilder(UserCurrencyManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->placeholder = new CurrencyPlaceholder($this->currencyManager);
    }

    public function testGetPlaceholder()
    {
        $this->assertSame(CurrencyPlaceholder::NAME, $this->placeholder->getPlaceholder());
    }

    public function testReplaceValue()
    {
        $this->assertSame("test_USD", $this->placeholder->replace("test_CURRENCY", ["CURRENCY" => "USD"]));
    }

    public function testReplaceDefault()
    {
        $this->currencyManager->expects($this->once())
            ->method("getUserCurrency")
            ->willReturn("USD");

        $this->assertSame("test_USD", $this->placeholder->replaceDefault("test_CURRENCY"));
    }

    public function testReplaceDefaultCplNotFound()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Can't get current currency");

        $this->currencyManager->expects($this->once())
            ->method("getUserCurrency")
            ->willReturn(null);

        $this->placeholder->replaceDefault("test_CURRENCY");
    }
}
