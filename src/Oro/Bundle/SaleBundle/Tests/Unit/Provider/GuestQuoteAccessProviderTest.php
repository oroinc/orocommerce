<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Provider;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SaleBundle\Provider\GuestQuoteAccessProvider;
use Oro\Bundle\SaleBundle\Tests\Unit\Stub\QuoteStub as Quote;
use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;

class GuestQuoteAccessProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var FeatureChecker|\PHPUnit_Framework_MockObject_MockObject */
    private $featureChecker;

    /** @var GuestQuoteAccessProvider */
    private $provider;

    protected function setUp()
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->provider = new GuestQuoteAccessProvider($this->featureChecker);
    }

    public function testIsGranted()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('guest_quote')
            ->willReturn(true);

        $quote = $this->getQuoteWithInternalStatus(Quote::INTERNAL_STATUS_SENT_TO_CUSTOMER);

        $this->assertTrue($this->provider->isGranted($quote));
    }

    public function testIsGrantedForExpiredQuote()
    {
        $quote = $this->getQuoteWithInternalStatus(Quote::INTERNAL_STATUS_SENT_TO_CUSTOMER);
        $quote->setExpired(true);

        $this->featureChecker->expects($this->never())
            ->method('isFeatureEnabled');

        $this->assertFalse($this->provider->isGranted($quote));
    }

    public function testIsGrantedForCorrectValidUntil()
    {
        $quote = $this->getQuoteWithInternalStatus(Quote::INTERNAL_STATUS_SENT_TO_CUSTOMER);
        $quote->setValidUntil(new \DateTime('2100-01-01', new \DateTimeZone('UTC')));

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('guest_quote')
            ->willReturn(true);

        $this->assertTrue($this->provider->isGranted($quote));
    }

    public function testIsGrantedForIncorrectValidUntil()
    {
        $quote = $this->getQuoteWithInternalStatus(Quote::INTERNAL_STATUS_SENT_TO_CUSTOMER);
        $quote->setValidUntil(new \DateTime('2010-01-01', new \DateTimeZone('UTC')));

        $this->featureChecker->expects($this->never())
            ->method('isFeatureEnabled');

        $this->assertFalse($this->provider->isGranted($quote));
    }

    public function testIsGrantedWhenFeatureDisabled()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('guest_quote')
            ->willReturn(false);

        $quote = $this->getQuoteWithInternalStatus(Quote::INTERNAL_STATUS_SENT_TO_CUSTOMER);

        $this->assertFalse($this->provider->isGranted($quote));
    }

    public function testIsGrantedWithoutInternalStatus()
    {
        $this->featureChecker->expects($this->never())
            ->method('isFeatureEnabled');

        $quote = new Quote();

        $this->assertFalse($this->provider->isGranted($quote));
    }

    public function testIsGrantedWithInvalidInternalStatus()
    {
        $this->featureChecker->expects($this->never())
            ->method('isFeatureEnabled');

        $quote = $this->getQuoteWithInternalStatus(Quote::INTERNAL_STATUS_DRAFT);

        $this->assertFalse($this->provider->isGranted($quote));
    }

    /**
     * @param string $status
     * @return Quote
     */
    private function getQuoteWithInternalStatus(string $status) : Quote
    {
        $quote = new Quote();
        $quote->setInternalStatus(new StubEnumValue($status, $status));

        return $quote;
    }
}
