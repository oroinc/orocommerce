<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Twig;

use Oro\Bundle\UIBundle\Twig;
use Oro\Bundle\TranslationBundle\Translation\Translator;

use OroB2B\Bundle\SaleBundle\Twig\QuoteExtension;

class QuoteExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var QuoteExtension
     */
    protected $extension;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Translator
     */
    protected $translator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Twig\Environment
     */
    protected $twigEnvironment;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->translator = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->twigEnvironment = $this->getMockBuilder('Oro\Bundle\UIBundle\Twig\Environment')
            ->disableOriginalConstructor()
            ->getMock();


        $this->extension = new QuoteExtension($this->translator, $this->twigEnvironment);
    }

    public function testGetFilters()
    {
        /* @var $filters \Twig_SimpleFilter[] */
        $filters = $this->extension->getFilters();

        $this->assertCount(1, $filters);

        $this->assertInstanceOf('Twig_SimpleFilter', $filters[0]);
        $this->assertEquals('orob2b_format_sale_quote_product_item', $filters[0]->getName());
    }

    public function testGetName()
    {
        $this->assertEquals(QuoteExtension::NAME, $this->extension->getName());
    }
}
