<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Twig;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Twig\QuoteGuestAccessExtension;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Resolver\WebsiteUrlResolver;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class QuoteGuestAccessExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var WebsiteUrlResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteUrlResolver;

    /** @var QuoteGuestAccessExtension */
    private $extension;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->websiteUrlResolver = $this->createMock(WebsiteUrlResolver::class);

        $container = self::getContainerBuilder()
            ->add(FeatureChecker::class, $this->featureChecker)
            ->add(WebsiteUrlResolver::class, $this->websiteUrlResolver)
            ->getContainer($this);

        $this->extension = new QuoteGuestAccessExtension($container);
    }

    public function testGetName(): void
    {
        $this->assertEquals(QuoteGuestAccessExtension::NAME, $this->extension->getName());
    }

    /**
     * @dataProvider guestAccessLinkProvider
     */
    public function testGetGuestAccessLink(bool $withWebsite, bool $isEnabled, ?string $expected): void
    {
        $quote = new Quote();
        $website = new Website();

        if ($withWebsite) {
            $quote->setWebsite($website);
        }

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('guest_quote')
            ->willReturn($isEnabled);

        $this->websiteUrlResolver->expects($this->any())
            ->method('getWebsitePath')
            ->with(
                'oro_sale_quote_frontend_view_guest',
                ['guest_access_id' => $quote->getGuestAccessId()],
                $website
            )
            ->willReturn($expected);

        self::assertEquals(
            $expected,
            self::callTwigFunction($this->extension, 'quote_guest_access_link', [$quote])
        );
    }

    public function guestAccessLinkProvider(): array
    {
        return [
            [
                'withWebsite' => false,
                'isEnabled' => false,
                'expected' => null
            ],
            [
                'withWebsite' => false,
                'isEnabled' => true,
                'expected' => null
            ],
            [
                'withWebsite' => true,
                'isEnabled' => false,
                'expected' => null
            ],
            [
                'withWebsite' => true,
                'isEnabled' => true,
                'expected' => '/some/test/url'
            ],
        ];
    }
}
