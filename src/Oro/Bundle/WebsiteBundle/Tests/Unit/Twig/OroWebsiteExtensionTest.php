<?php

namespace Oro\Bundle\WebsiteBundle\Tests\Unit\Twig;

use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Bundle\WebsiteBundle\Twig\OroWebsiteExtension;

class OroWebsiteExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OroWebsiteExtension
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
        $this->websiteManager = $this->getMockBuilder('Oro\Bundle\WebsiteBundle\Manager\WebsiteManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new OroWebsiteExtension($this->websiteManager);
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
        $this->assertEquals(OroWebsiteExtension::NAME, $this->extension->getName());
    }
}
