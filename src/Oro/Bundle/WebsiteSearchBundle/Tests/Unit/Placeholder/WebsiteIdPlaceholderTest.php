<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Placeholder;

use Oro\Bundle\WebsiteSearchBundle\Placeholder\WebsiteIdPlaceholder;

use OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager;

class WebsiteIdPlaceholderTest extends \PHPUnit_Framework_TestCase
{
    /** @var WebsiteManager|\PHPUnit_Framework_MockObject_MockObject */
    private $websiteManager;

    /** @var WebsiteIdPlaceholder */
    private $placeholder;

    protected function setUp()
    {
        $this->websiteManager = $this->getMockBuilder('OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->placeholder = new WebsiteIdPlaceholder($this->websiteManager);
    }

    protected function tearDown()
    {
        unset($this->websiteManager, $this->placeholder);
    }

    public function testGetPlaceholder()
    {
        $this->assertInternalType('string', $this->placeholder->getPlaceholder());
        $this->assertEquals('WEBSITE_ID', $this->placeholder->getPlaceholder());
    }

    public function testGetValueWithWebsiteId()
    {
        $website = $this->getMockBuilder('OroB2B\Bundle\WebsiteBundle\Entity\Website')
            ->disableOriginalConstructor()
            ->getMock();

        $website->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $value = $this->placeholder->getValue();

        $this->assertInternalType('string', $value);
        $this->assertEquals('1', $value);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Current website is not defined.
     */
    public function testGetValueWithoutWebsiteId()
    {
        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn(null);

        $this->placeholder->getValue();
    }
}
