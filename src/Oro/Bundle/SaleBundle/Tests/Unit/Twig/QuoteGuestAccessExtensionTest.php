<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Twig;

use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Provider\GuestQuoteAccessProviderInterface;
use Oro\Bundle\SaleBundle\Twig\QuoteGuestAccessExtension;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Resolver\WebsiteUrlResolver;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class QuoteGuestAccessExtensionTest extends \PHPUnit_Framework_TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var GuestQuoteAccessProviderInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $guestLinkAccessProvider;

    /** @var WebsiteUrlResolver|\PHPUnit_Framework_MockObject_MockObject */
    private $websiteUrlResolver;

    /** @var QuoteGuestAccessExtension */
    private $extension;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->guestLinkAccessProvider = $this->createMock(GuestQuoteAccessProviderInterface::class);
        $this->websiteUrlResolver = $this->createMock(WebsiteUrlResolver::class);

        $container = self::getContainerBuilder()
            ->add('oro_sale.provider.guest_quote_access.link', $this->guestLinkAccessProvider)
            ->add('oro_website.resolver.website_url_resolver', $this->websiteUrlResolver)
            ->getContainer($this);

        $this->extension = new QuoteGuestAccessExtension($container);
    }

    public function testGetName(): void
    {
        $this->assertEquals(QuoteGuestAccessExtension::NAME, $this->extension->getName());
    }

    /**
     * @dataProvider guestAccessLinkProvider
     *
     * @param bool $withWebsite
     * @param bool $isGranted
     * @param null|string $expected
     */
    public function testGetGuestAccessLink(bool $withWebsite, bool $isGranted, ?string $expected): void
    {
        $quote = new Quote();
        $website = new Website();

        if ($withWebsite) {
            $quote->setWebsite($website);
        }

        $this->guestLinkAccessProvider->expects($this->any())
            ->method('isGranted')
            ->with($quote)
            ->willReturn($isGranted);

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

    /**
     * @return array
     */
    public function guestAccessLinkProvider(): array
    {
        return [
            [
                'withWebsite' => false,
                'isGranted' => false,
                'expected' => null
            ],
            [
                'withWebsite' => false,
                'isGranted' => true,
                'expected' => null
            ],
            [
                'withWebsite' => true,
                'isGranted' => false,
                'expected' => null
            ],
            [
                'withWebsite' => true,
                'isGranted' => true,
                'expected' => '/some/test/url'
            ],
        ];
    }
}
