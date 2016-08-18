<?php

namespace OroB2B\src\OroB2B\Bundle\OrderBundle\Tests\Unit\Twig;

use OroB2B\Bundle\OrderBundle\Formatter\SourceDocumentFormatter;
use OroB2B\Bundle\OrderBundle\Twig\OrderExtension;

class OrderExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SourceDocumentFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $sourceDocumentFormatter;

    /**
     * @var OrderExtension
     */
    protected $extension;

    public function setUp()
    {
        $this->sourceDocumentFormatter = $this
            ->getMockBuilder('OroB2B\Bundle\OrderBundle\Formatter\SourceDocumentFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extension = new OrderExtension($this->sourceDocumentFormatter);
    }

    public function testGetName()
    {
        $this->assertEquals(OrderExtension::NAME, $this->extension->getName());
    }

    public function testGetFilters()
    {
        $expected = [
            new \Twig_SimpleFilter(
                'orob2b_order_format_source_document',
                [$this->sourceDocumentFormatter, 'format'],
                ['is_safe' => ['html']]
            ),
        ];
        $this->assertEquals($expected, $this->extension->getFilters());
    }
}
