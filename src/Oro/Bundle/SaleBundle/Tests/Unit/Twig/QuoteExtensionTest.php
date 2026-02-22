<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Twig;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
class QuoteExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private QuoteProductFormatter&MockObject $quoteProductFormatter;
    private WebsiteUrlResolver&MockObject $websiteUrlResolver;
    private ConfigManager&MockObject $configManager;
    private FeatureChecker&MockObject $featureChecker;
    private QuoteExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->quoteProductFormatter = $this->createMock(QuoteProductFormatter::class);
        $this->websiteUrlResolver = $this->createMock(WebsiteUrlResolver::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $container = self::getContainerBuilder()
            ->add(QuoteProductFormatter::class, $this->quoteProductFormatter)
            ->add(WebsiteUrlResolver::class, $this->websiteUrlResolver)
            ->add(ConfigManager::class, $this->configManager)
            ->add(FeatureChecker::class, $this->featureChecker)
            ->getContainer($this);

        $this->extension = new QuoteExtension($container);
    }

    public function testFormatProductType()
    {
        $type = 123;
        $expected = 'result';

        $this->quoteProductFormatter->expects(self::once())
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

        $this->quoteProductFormatter->expects(self::once())
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

        $this->quoteProductFormatter->expects(self::once())
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
        $this->configManager->expects(self::any())
            ->method('get')
            ->with('oro_rfp.frontend_product_visibility')
            ->willReturn([
                ExtendHelper::buildEnumOptionId(
                    Product::INVENTORY_STATUS_ENUM_CODE,
                    Product::INVENTORY_STATUS_OUT_OF_STOCK
                )
            ]);

        $product = new StubProduct();

        if (!empty($productInventoryStatus)) {
            $productInventoryStatus = new TestEnumValue(
                Product::INVENTORY_STATUS_ENUM_CODE,
                'Test',
                $productInventoryStatus
            );
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

        $this->featureChecker->expects(self::any())
            ->method('isFeatureEnabled')
            ->with('guest_quote')
            ->willReturn($isEnabled);

        $this->websiteUrlResolver->expects(self::any())
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
