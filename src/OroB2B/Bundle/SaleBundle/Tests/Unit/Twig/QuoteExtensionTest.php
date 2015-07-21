<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Twig;

use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\SaleBundle\Twig\QuoteExtension;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductRequest;
use OroB2B\Bundle\SaleBundle\Formatter\QuoteProductFormatter;

/**
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
class QuoteExtensionTest extends \PHPUnit_Framework_TestCase
{
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
            ->getMock();

        $this->extension = new QuoteExtension(
            $this->quoteProductFormatter
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
}
