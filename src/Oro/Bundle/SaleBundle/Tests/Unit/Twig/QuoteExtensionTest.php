<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Twig;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product as StubProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Entity\QuoteProductRequest;
use Oro\Bundle\SaleBundle\Formatter\QuoteProductFormatter;
use Oro\Bundle\SaleBundle\Twig\QuoteExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

/**
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
class QuoteExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    const FRONTEND_SYSTEM_CONFIG_PATH = 'oro_rfp.frontend_product_visibility';

    /** @var QuoteExtension */
    protected $extension;

    /** @var \PHPUnit\Framework\MockObject\MockObject|QuoteProductFormatter */
    protected $quoteProductFormatter;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager */
    protected $configManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->quoteProductFormatter = $this->createMock(QuoteProductFormatter::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $container = self::getContainerBuilder()
            ->add('oro_sale.formatter.quote_product', $this->quoteProductFormatter)
            ->add('oro_config.manager', $this->configManager)
            ->getContainer($this);

        $this->extension = new QuoteExtension($container);
    }

    public function testGetName()
    {
        $this->assertEquals(QuoteExtension::NAME, $this->extension->getName());
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
     * @param string $productInventoryStatus
     * @param bool $expectedResult
     */
    public function testIsQuoteVisible($productInventoryStatus, $expectedResult)
    {
        $this->configManager->expects($this->any())
            ->method('get')
            ->with(self::FRONTEND_SYSTEM_CONFIG_PATH)
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

    /**
     * @return array
     */
    public function getInventoryStatus()
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
}
