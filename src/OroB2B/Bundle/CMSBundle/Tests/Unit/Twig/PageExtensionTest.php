<?php

namespace OroB2B\Bundle\CMSBundle\Tests\Unit\Twig;

use OroB2B\Bundle\CMSBundle\JsTree\PageTreeHandler;
use OroB2B\Bundle\CMSBundle\Twig\PageExtension;

class PageExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PageTreeHandler
     */
    protected $pageTreeHandler;

    /**
     * @var PageExtension
     */
    protected $extension;

    public function setUp()
    {
        $this->pageTreeHandler = $this->getMockBuilder('OroB2B\Bundle\CMSBundle\JsTree\PageTreeHandler')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extension = new PageExtension($this->pageTreeHandler);
    }

    public function testGetName()
    {
        $this->assertEquals(PageExtension::NAME, $this->extension->getName());
    }

    /**
     * Test related method
     */
    public function testGetFunctions()
    {
        $functions = $this->extension->getFunctions();
        $this->assertCount(1, $functions);

        /** @var \Twig_SimpleFunction $function */
        $function = $functions[0];
        $this->assertInstanceOf('\Twig_SimpleFunction', $function);
        $this->assertEquals('orob2b_page_list', $function->getName());
        $this->assertEquals([$this->extension, 'getPageList'], $function->getCallable());
    }

    public function testGetPageList()
    {
        $tree = [
            'id' => 1,
            'parent' => '#',
            'text' => 'Root Page',
            'state' => [
                'opened' => true
            ]
        ];

        $this->pageTreeHandler->expects($this->once())
            ->method('createTree')
            ->will($this->returnValue($tree));

        $result = $this->extension->getPageList();
        $this->assertEquals($tree, $result);
    }
}
