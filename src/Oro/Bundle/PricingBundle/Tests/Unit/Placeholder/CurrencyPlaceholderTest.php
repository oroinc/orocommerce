<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Placeholder;

use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Placeholder\CurrencyPlaceholder;

class CurrencyPlaceholderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CurrencyPlaceholder
     */
    private $placeholder;

    /**
     * @var UserCurrencyManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $currencyManager;

    protected function setUp()
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

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Can't get current currency
     */
    public function testReplaceDefaultCplNotFound()
    {
        $this->currencyManager->expects($this->once())
            ->method("getUserCurrency")
            ->willReturn(null);

        $this->placeholder->replaceDefault("test_CURRENCY");
    }
}
