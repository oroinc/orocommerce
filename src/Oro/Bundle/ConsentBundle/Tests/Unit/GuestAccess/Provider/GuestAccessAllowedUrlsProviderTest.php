<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\GuestAccess\Provider;

use Oro\Bundle\ConsentBundle\Builder\CmsPageDataBuilder;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\GuestAccess\Provider\GuestAccessAllowedUrlsProvider;
use Oro\Bundle\ConsentBundle\Model\CmsPageData;
use Oro\Bundle\ConsentBundle\Provider\EnabledConsentProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RequestContext;

class GuestAccessAllowedUrlsProviderTest extends TestCase
{
    /** @var EnabledConsentProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $consentProvider;

    /** @var CmsPageDataBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $cmsPageDataBuilder;

    /** @var RequestContext|\PHPUnit\Framework\MockObject\MockObject */
    private $requestContext;

    /** @var GuestAccessAllowedUrlsProvider */
    private $guestAccessAllowedUrlsProvider;

    protected function setUp(): void
    {
        $this->consentProvider = $this->createMock(EnabledConsentProvider::class);
        $this->cmsPageDataBuilder = $this->createMock(CmsPageDataBuilder::class);
        $this->requestContext = $this->createMock(RequestContext::class);

        $this->guestAccessAllowedUrlsProvider = new GuestAccessAllowedUrlsProvider(
            $this->consentProvider,
            $this->cmsPageDataBuilder,
            $this->requestContext
        );
    }

    public function testGetAllowedUrlsPatternsWithoutConsents()
    {
        $pattern = '^/consent-pattern$';

        $this->consentProvider->expects($this->once())
            ->method('getConsents')
            ->willReturn([]);

        $this->guestAccessAllowedUrlsProvider->addAllowedUrlPattern($pattern);

        $this->assertEquals([$pattern], $this->guestAccessAllowedUrlsProvider->getAllowedUrlsPatterns());
    }

    public function testGetAllowedUrlsPatterns()
    {
        $urlA = '/url-1';
        $urlB = '/url-2';
        $patternC = '^/consent-pattern$';
        $expectedUrls = [$patternC, '^' . $urlA . '$', '^' . $urlB . '$'];

        $consentA = $this->createMock(Consent::class);
        $consentB = $this->createMock(Consent::class);
        $consentC = $this->createMock(Consent::class);
        $this->consentProvider->expects($this->once())
            ->method('getConsents')
            ->willReturn([$consentA, $consentB, $consentC]);

        $baseUrl = '/index_dev.php';
        $cmsPageDataA = $this->createMock(CmsPageData::class);
        $cmsPageDataA->expects($this->once())
            ->method('getUrl')
            ->willReturn($baseUrl . $urlA);
        $cmsPageDataB = $this->createMock(CmsPageData::class);
        $cmsPageDataB->expects($this->once())
            ->method('getUrl')
            ->willReturn($urlB);
        $this->cmsPageDataBuilder->expects($this->exactly(3))
            ->method('build')
            ->withConsecutive([$consentA], [$consentB], [$consentC])
            ->willReturnOnConsecutiveCalls($cmsPageDataA, $cmsPageDataB, null);

        $this->requestContext->expects($this->once())
            ->method('getBaseUrl')
            ->willReturn($baseUrl);

        $this->guestAccessAllowedUrlsProvider->addAllowedUrlPattern($patternC);

        $this->assertEquals($expectedUrls, $this->guestAccessAllowedUrlsProvider->getAllowedUrlsPatterns());
    }
}
