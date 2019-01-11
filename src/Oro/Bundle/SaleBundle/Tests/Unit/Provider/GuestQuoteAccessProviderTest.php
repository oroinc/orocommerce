<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Provider;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SaleBundle\Provider\GuestQuoteAccessProvider;
use Oro\Bundle\SaleBundle\Tests\Unit\Stub\QuoteStub as Quote;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;
use Oro\Component\Testing\Unit\EntityTrait;

class GuestQuoteAccessProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var WebsiteManager|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteManager;

    /** @var GuestQuoteAccessProvider */
    private $provider;

    protected function setUp()
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);

        $this->provider = new GuestQuoteAccessProvider($this->featureChecker, $this->websiteManager);
    }

    public function testIsGranted()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('guest_quote')
            ->willReturn(true);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($this->getWebsite(42));

        $this->assertTrue($this->provider->isGranted($this->getQuote(Quote::INTERNAL_STATUS_SENT_TO_CUSTOMER, 42)));
    }

    public function testIsGrantedForExpiredQuote()
    {
        $quote = $this->getQuote(Quote::INTERNAL_STATUS_SENT_TO_CUSTOMER, 42);
        $quote->setExpired(true);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('guest_quote')
            ->willReturn(true);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($this->getWebsite(42));

        $this->assertTrue($this->provider->isGranted($quote));
    }

    public function testIsGrantedForCorrectValidUntil()
    {
        $quote = $this->getQuote(Quote::INTERNAL_STATUS_SENT_TO_CUSTOMER, 42);
        $quote->setValidUntil(new \DateTime('2100-01-01', new \DateTimeZone('UTC')));

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('guest_quote')
            ->willReturn(true);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($this->getWebsite(42));

        $this->assertTrue($this->provider->isGranted($quote));
    }

    public function testIsGrantedForIncorrectValidUntil()
    {
        $quote = $this->getQuote(Quote::INTERNAL_STATUS_SENT_TO_CUSTOMER, 42);
        $quote->setValidUntil(new \DateTime('2010-01-01', new \DateTimeZone('UTC')));

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('guest_quote')
            ->willReturn(true);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($this->getWebsite(42));

        $this->assertTrue($this->provider->isGranted($quote));
    }

    public function testIsGrantedWhenFeatureDisabled()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('guest_quote')
            ->willReturn(false);

        $this->websiteManager->expects($this->never())
            ->method('getCurrentWebsite');

        $this->assertFalse($this->provider->isGranted($this->getQuote(Quote::INTERNAL_STATUS_SENT_TO_CUSTOMER)));
    }

    public function testIsGrantedWithoutInternalStatus()
    {
        $quote = new Quote();
        $quote->setWebsite($this->getWebsite(42));

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('guest_quote')
            ->willReturn(true);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($this->getWebsite(42));

        $this->assertFalse($this->provider->isGranted(new Quote()));
    }

    public function testIsGrantedWithInvalidInternalStatus()
    {
        $this->featureChecker->expects($this->never())
            ->method('isFeatureEnabled');

        $this->websiteManager->expects($this->never())
            ->method('getCurrentWebsite');

        $this->assertFalse($this->provider->isGranted($this->getQuote(Quote::INTERNAL_STATUS_DRAFT)));
    }

    public function testIsGrantedWithoutCurrentWebsite()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('guest_quote')
            ->willReturn(true);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn(null);

        $this->assertFalse($this->provider->isGranted($this->getQuote(Quote::INTERNAL_STATUS_SENT_TO_CUSTOMER, 42)));
    }

    public function testIsGrantedWithIncorrectCurrentWebsite()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('guest_quote')
            ->willReturn(true);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($this->getWebsite(100));

        $this->assertFalse($this->provider->isGranted($this->getQuote(Quote::INTERNAL_STATUS_SENT_TO_CUSTOMER, 42)));
    }

    public function testIsGrantedWithoutWebsiteInQuote()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('guest_quote')
            ->willReturn(true);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($this->getWebsite(42));

        $this->assertFalse($this->provider->isGranted($this->getQuote(Quote::INTERNAL_STATUS_SENT_TO_CUSTOMER)));
    }

    /**
     * @param string $status
     * @param int|null $websiteId
     * @return Quote
     */
    private function getQuote(string $status, ?int $websiteId = null) : Quote
    {
        $quote = new Quote();
        $quote->setInternalStatus(new StubEnumValue($status, $status));

        if ($websiteId) {
            $quote->setWebsite($this->getWebsite($websiteId));
        }

        return $quote;
    }

    /**
     * @param int $websiteId
     * @return object|Website
     */
    private function getWebsite(int $websiteId): Website
    {
        return $this->getEntity(Website::class, ['id' => $websiteId]);
    }
}
