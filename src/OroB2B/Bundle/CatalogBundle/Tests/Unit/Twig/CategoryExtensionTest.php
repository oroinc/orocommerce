<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Unit\Twig;

use OroB2B\Bundle\CatalogBundle\JsTree\CategoryTreeHandler;
use OroB2B\Bundle\CatalogBundle\Twig\CategoryExtension;

class CategoryExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CategoryTreeHandler
     */
    protected $categoryTreeHandler;

    /**
     * @var CategoryExtension
     */
    protected $extension;

    public function setUp()
    {
        $this->categoryTreeHandler = $this->getMockBuilder('OroB2B\Bundle\CatalogBundle\JsTree\CategoryTreeHandler')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extension = new CategoryExtension($this->categoryTreeHandler);
    }

    public function testGetName()
    {
        $this->assertEquals(CategoryExtension::NAME, $this->extension->getName());
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
        $this->assertEquals('orob2b_category_list', $function->getName());
        $this->assertEquals([$this->extension, 'getCategoryList'], $function->getCallable());
    }

    public function testGetCategoryList()
    {
        $tree = [
            'id' => 1,
            'parent' => '#',
            'text' => 'Master catalog',
            'state' => [
                'opened' => true
            ]
        ];

        $this->categoryTreeHandler->expects($this->once())
            ->method('createTree')
            ->will($this->returnValue($tree));

        $result = $this->extension->getCategoryList();
        $this->assertEquals($tree, $result);
    }
}
