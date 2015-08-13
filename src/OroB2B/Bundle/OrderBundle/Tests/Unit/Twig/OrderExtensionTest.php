<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Twig;

use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\OrderBundle\Entity\OrderProductItem;
use OroB2B\Bundle\OrderBundle\Formatter\OrderProductFormatter;
use OroB2B\Bundle\OrderBundle\Twig\OrderExtension;

class OrderExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OrderExtension
     */
    protected $extension;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var OrderProductFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderProductFormatter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->orderProductFormatter = $this->getMockBuilder(
            'OroB2B\Bundle\OrderBundle\Formatter\OrderProductFormatter'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new OrderExtension(
            $this->orderProductFormatter
        );
    }

    public function testGetFilters()
    {
        /* @var $filters \Twig_SimpleFilter[] */
        $filters = $this->extension->getFilters();

        $this->assertCount(1, $filters);

        $this->assertInstanceOf('Twig_SimpleFilter', $filters[0]);
        $this->assertEquals('orob2b_format_order_product_item', $filters[0]->getName());
    }

    public function testGetName()
    {
        $this->assertEquals(OrderExtension::NAME, $this->extension->getName());
    }

    public function testFormatProductItem()
    {
        $this->orderProductFormatter->expects($this->once())
            ->method('formatItem')
            ->with(new OrderProductItem())
        ;

        $this->extension->formatProductItem(new OrderProductItem());
    }
}
