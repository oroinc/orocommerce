<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Twig;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product as StubProduct;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Entity\QuoteProductRequest;
use Oro\Bundle\SaleBundle\Formatter\QuoteProductFormatter;
use Oro\Bundle\SaleBundle\Twig\QuoteExtension;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Resolver\WebsiteUrlResolver;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

/**
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
class QuoteExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var WebsiteUrlResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteUrlResolver;

    /** @var \PHPUnit\Framework\MockObject\MockObject|QuoteProductFormatter */
    private $quoteProductFormatter;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager */
    private $configManager;

    /** @var QuoteExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->websiteUrlResolver = $this->createMock(WebsiteUrlResolver::class);
        $this->quoteProductFormatter = $this->createMock(QuoteProductFormatter::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $container = self::getContainerBuilder()
            ->add(FeatureChecker::class, $this->featureChecker)
            ->add(WebsiteUrlResolver::class, $this->websiteUrlResolver)
            ->add('oro_sale.formatter.quote_product', $this->quoteProductFormatter)
            ->add('oro_config.manager', $this->configManager)
            ->getContainer($this);

        $this->extension = new QuoteExtension($container);
    }

    public function testFormatProductType()
    {
        $type = 123;
        $expected = 'result';

        $this->quoteProductFormatter->expects($this->once())
            ->method('formatType')
            ->with($type)
            ->willReturn($expected);

        self::assertEquals(
            $expected,
            self::callTwigFilter($this->extension, 'oro_format_sale_quote_product_type', [$type])
        );
    }

    public function testFormatProductOffer()
    {
        $item = new QuoteProductOffer();
        $expected = 'result';

        $this->quoteProductFormatter->expects($this->once())
            ->method('formatOffer')
            ->with(self::identicalTo($item))
            ->willReturn($expected);

        self::assertEquals(
            $expected,
            self::callTwigFilter($this->extension, 'oro_format_sale_quote_product_offer', [$item])
        );
    }

    public function testFormatProductRequest()
    {
        $item = new QuoteProductRequest();
        $expected = 'result';

        $this->quoteProductFormatter->expects($this->once())
            ->method('formatRequest')
            ->with(self::identicalTo($item))
            ->willReturn($expected);

        self::assertEquals(
            $expected,
            self::callTwigFilter($this->extension, 'oro_format_sale_quote_product_request', [$item])
        );
    }

    /**
     * @dataProvider getInventoryStatus
     */
    public function testIsQuoteVisible(string $productInventoryStatus, bool $expectedResult)
    {
        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_rfp.frontend_product_visibility')
            ->willReturn([Product::INVENTORY_STATUS_OUT_OF_STOCK]);

        $product = new StubProduct();

        if (!empty($productInventoryStatus)) {
            $productInventoryStatus = new TestEnumValue($productInventoryStatus, $productInventoryStatus);
            $product->setInventoryStatus($productInventoryStatus);
        }

        self::assertEquals(
            $expectedResult,
            self::callTwigFunction($this->extension, 'is_quote_visible', [$product])
        );
    }

    public function getInventoryStatus(): array
    {
        return [
            [
                'productInventoryStatus' => Product::INVENTORY_STATUS_IN_STOCK,
                'expectedResult' => false
            ],
            [
                'productInventoryStatus' => Product::INVENTORY_STATUS_OUT_OF_STOCK,
                'expectedResult' => true
            ],
        ];
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
