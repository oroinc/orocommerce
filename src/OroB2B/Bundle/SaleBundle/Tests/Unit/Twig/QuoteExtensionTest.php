<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Twig;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProduct;

use OroB2B\Bundle\SaleBundle\Twig\QuoteExtension;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductRequest;
use OroB2B\Bundle\SaleBundle\Formatter\QuoteProductFormatter;

use Symfony\Component\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
class QuoteExtensionTest extends \PHPUnit_Framework_TestCase
{
    const FRONTEND_SYSTEM_CONFIG_PATH = 'oro_b2b_rfp.frontend_product_visibility';

    /**
     * @var QuoteExtension
     */
    protected $extension;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|QuoteProductFormatter
     */
    protected $quoteProductFormatter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager
     */
    protected $configManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->quoteProductFormatter = $this->getMockBuilder(
            'OroB2B\Bundle\SaleBundle\Formatter\QuoteProductFormatter'
        )
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->configManager = $this->getMockBuilder(
            'Oro\Bundle\ConfigBundle\Config\ConfigManager'
        )
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->extension = new QuoteExtension(
            $this->quoteProductFormatter,
            $this->configManager
        );
    }

    public function testGetFilters()
    {
        /* @var $filters \Twig_SimpleFilter[] */
        $filters = $this->extension->getFilters();

        $this->assertCount(3, $filters);

        $this->assertInstanceOf('Twig_SimpleFilter', $filters[0]);
        $this->assertEquals('orob2b_format_sale_quote_product_offer', $filters[0]->getName());

        $this->assertInstanceOf('Twig_SimpleFilter', $filters[1]);
        $this->assertEquals('orob2b_format_sale_quote_product_type', $filters[1]->getName());

        $this->assertInstanceOf('Twig_SimpleFilter', $filters[2]);
        $this->assertEquals('orob2b_format_sale_quote_product_request', $filters[2]->getName());
    }

    public function testGetFunctions()
    {
        $this->assertEquals(
            [
                new \Twig_SimpleFunction('is_quote_visible', [$this->extension, 'isQuoteVisible'])
            ],
            $this->extension->getFunctions()
        );
    }

    public function testGetName()
    {
        $this->assertEquals(QuoteExtension::NAME, $this->extension->getName());
    }

    public function testFormatProductType()
    {
        $this->quoteProductFormatter->expects($this->once())
            ->method('formatType')
            ->with(123)
        ;

        $this->extension->formatProductType(123);
    }

    public function testFormatProductOffer()
    {
        $this->quoteProductFormatter->expects($this->once())
            ->method('formatOffer')
            ->with(new QuoteProductOffer())
        ;

        $this->extension->formatProductOffer(new QuoteProductOffer());
    }

    public function testFormatProductRequest()
    {
        $this->quoteProductFormatter->expects($this->once())
            ->method('formatRequest')
            ->with(new QuoteProductRequest())
        ;

        $this->extension->formatProductRequest(new QuoteProductRequest());
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
            ->willReturn([Product::INVENTORY_STATUS_OUT_OF_STOCK])
        ;

        $product = new StubProduct();

        if (!empty($productInventoryStatus)) {
            $productInventoryStatus = new StubEnumValue($productInventoryStatus, $productInventoryStatus);
            $product->setInventoryStatus($productInventoryStatus);
        }

        $this->assertEquals($expectedResult, $this->extension->isQuoteVisible($product));
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
