<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Placeholder;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\WebsiteIdPlaceholder;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

class WebsiteIdPlaceholderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var WebsiteManager|\PHPUnit_Framework_MockObject_MockObject */
    private $websiteManager;

    /** @var WebsiteIdPlaceholder */
    private $placeholder;

    protected function setUp()
    {
        $this->websiteManager = $this->getMockBuilder(WebsiteManager::class)
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

    public function testReplaceDefaultWithWebsiteId()
    {
        /** @var Website $website */
        $website = $this->getEntity('Oro\Bundle\WebsiteBundle\Entity\Website', ['id' => 1]);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $value = $this->placeholder->replaceDefault('string_WEBSITE_ID');

        $this->assertInternalType('string', $value);
        $this->assertEquals('string_1', $value);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Current website is not defined.
     */
    public function testReplaceWithoutWebsiteId()
    {
        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn(null);

        $this->assertEquals(
            'string_WEBSITE_ID',
            $this->placeholder->replaceDefault('string_WEBSITE_ID')
        );
    }

    public function testReplace()
    {
        $this->websiteManager->expects($this->never())->method($this->anything());

        $this->assertEquals(
            'string_1',
            $this->placeholder->replace('string_WEBSITE_ID', ['WEBSITE_ID' => 1])
        );
    }

    public function testReplaceWithoutValue()
    {
        $this->websiteManager->expects($this->never())->method($this->anything());

        $this->assertEquals(
            'string_WEBSITE_ID',
            $this->placeholder->replace('string_WEBSITE_ID', ['NON_WEBSITE_ID' => 1])
        );
    }
}
