<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Provider;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Provider\GuestQuoteAccessLinkProvider;

class GuestQuoteAccessLinkProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var FeatureChecker|\PHPUnit_Framework_MockObject_MockObject */
    private $featureChecker;

    /** @var GuestQuoteAccessLinkProvider */
    private $provider;

    /** @var Quote */
    private $quote;

    protected function setUp()
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->provider = new GuestQuoteAccessLinkProvider($this->featureChecker);

        $this->quote = new Quote();
    }

    public function testIsGranted()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('guest_quote')
            ->willReturn(true);

        $this->assertTrue($this->provider->isGranted($this->quote));
    }

    public function testIsGrantedForExpiredQuote()
    {
        $this->quote->setExpired(true);

        $this->featureChecker->expects($this->never())
            ->method('isFeatureEnabled');

        $this->assertFalse($this->provider->isGranted($this->quote));
    }

    public function testIsGrantedForCorrectValidUntil()
    {
        $this->quote->setValidUntil(new \DateTime('2100-01-01', new \DateTimeZone('UTC')));

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('guest_quote')
            ->willReturn(true);

        $this->assertTrue($this->provider->isGranted($this->quote));
    }

    public function testIsGrantedForIncorrectValidUntil()
    {
        $this->quote->setValidUntil(new \DateTime('2010-01-01', new \DateTimeZone('UTC')));

        $this->featureChecker->expects($this->never())
            ->method('isFeatureEnabled');

        $this->assertFalse($this->provider->isGranted($this->quote));
    }

    public function testIsGrantedWhenFeatureDisabled()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('guest_quote')
            ->willReturn(false);

        $this->assertFalse($this->provider->isGranted($this->quote));
    }
}
