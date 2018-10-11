<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Provider;

use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Provider\GuestQuoteAccessFrontendProvider;
use Oro\Bundle\SaleBundle\Provider\GuestQuoteAccessProviderInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Testing\Unit\EntityTrait;

class GuestQuoteAccessFrontendProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var GuestQuoteAccessProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerProvider;

    /** @var WebsiteManager|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteManager;

    /** @var GuestQuoteAccessFrontendProvider */
    private $provider;

    protected function setUp()
    {
        $this->innerProvider = $this->createMock(GuestQuoteAccessProviderInterface::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);

        $this->provider = new GuestQuoteAccessFrontendProvider($this->innerProvider, $this->websiteManager);
    }

    public function testIsGranted(): void
    {
        $quote = $this->getQuoteWithWebsite(42);

        $this->innerProvider->expects($this->once())
            ->method('isGranted')
            ->with($quote)
            ->willReturn(true);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($this->getWebsite(42));

        $this->assertTrue($this->provider->isGranted($quote));
    }

    public function testIsNotGrantedByInnerProvider(): void
    {
        $quote = $this->getQuoteWithWebsite(42);

        $this->innerProvider->expects($this->once())
            ->method('isGranted')
            ->with($quote)
            ->willReturn(false);

        $this->websiteManager->expects($this->never())
            ->method('getCurrentWebsite');

        $this->assertFalse($this->provider->isGranted($quote));
    }

    public function testIsGrantedWithoutCurrentWebsite(): void
    {
        $quote = $this->getQuoteWithWebsite(42);

        $this->innerProvider->expects($this->once())
            ->method('isGranted')
            ->with($quote)
            ->willReturn(true);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn(null);

        $this->assertTrue($this->provider->isGranted($quote));
    }

    public function testIsGrantedWithIncorrectCurrentWebsite(): void
    {
        $quote = $this->getQuoteWithWebsite(42);

        $this->innerProvider->expects($this->once())
            ->method('isGranted')
            ->with($quote)
            ->willReturn(true);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($this->getWebsite(100));

        $this->assertFalse($this->provider->isGranted($quote));
    }

    public function testIsGrantedWithoutWebsiteInQuote(): void
    {
        $quote = $this->getQuoteWithWebsite();

        $this->innerProvider->expects($this->once())
            ->method('isGranted')
            ->with($quote)
            ->willReturn(true);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($this->getWebsite(100));

        $this->assertFalse($this->provider->isGranted($quote));
    }

    /**
     * @param int|null $websiteId
     * @return Quote
     */
    private function getQuoteWithWebsite(?int $websiteId = null): Quote
    {
        $quote = new Quote();

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
