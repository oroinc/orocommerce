<?php

namespace OroB2B\Bundle\WebsiteBundle\Tests\Unit\Twig;

use OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager;
use OroB2B\Bundle\WebsiteBundle\Twig\OroB2BWebsiteExtension;

class OroB2BWebsiteExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OroB2BWebsiteExtension
     */
    protected $extension;

    /**
     * @var WebsiteManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $websiteManager;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->websiteManager = $this->getMockBuilder('OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new OroB2BWebsiteExtension($this->websiteManager);
    }

    public function testGetFunctions()
    {
        $result = $this->extension->getFunctions();
        $functions = [
            'orob2b_website_get_current_website'
        ];

        /** @var $function \Twig_SimpleFunction */
        foreach ($result as $function) {
            $this->assertTrue(in_array($function->getName(), $functions));
        }
    }

    public function testGetName()
    {
        $this->assertEquals(OroB2BWebsiteExtension::NAME, $this->extension->getName());
    }
}
