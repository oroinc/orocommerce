<?php

namespace Oro\src\Oro\Bundle\OrderBundle\Tests\Unit\Twig;

use Oro\Bundle\OrderBundle\Formatter\SourceDocumentFormatter;
use Oro\Bundle\OrderBundle\Twig\OrderExtension;

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
            ->getMockBuilder('Oro\Bundle\OrderBundle\Formatter\SourceDocumentFormatter')
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
