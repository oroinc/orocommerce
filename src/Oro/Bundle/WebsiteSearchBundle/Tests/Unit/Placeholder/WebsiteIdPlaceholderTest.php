<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Placeholder;

use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\WebsiteIdPlaceholder;
use Oro\Component\Testing\ReflectionUtil;

class WebsiteIdPlaceholderTest extends \PHPUnit\Framework\TestCase
{
    /** @var WebsiteManager|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteManager;

    /** @var WebsiteIdPlaceholder */
    private $placeholder;

    protected function setUp(): void
    {
        $this->websiteManager = $this->createMock(WebsiteManager::class);

        $this->placeholder = new WebsiteIdPlaceholder($this->websiteManager);
    }

    public function testGetPlaceholder()
    {
        $this->assertIsString($this->placeholder->getPlaceholder());
        $this->assertEquals('WEBSITE_ID', $this->placeholder->getPlaceholder());
    }

    public function testReplaceDefaultWithWebsiteId()
    {
        $website = new Website();
        ReflectionUtil::setId($website, 1);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $value = $this->placeholder->replaceDefault('string_WEBSITE_ID');

        $this->assertIsString($value);
        $this->assertEquals('string_1', $value);
    }

    public function testReplaceWithoutWebsiteId()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Current website is not defined.');

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
        $this->websiteManager->expects($this->never())
            ->method($this->anything());

        $this->assertEquals(
            'string_1',
            $this->placeholder->replace('string_WEBSITE_ID', ['WEBSITE_ID' => 1])
        );
    }

    public function testReplaceWithoutValue()
    {
        $this->websiteManager->expects($this->never())
            ->method($this->anything());

        $this->assertEquals(
            'string_WEBSITE_ID',
            $this->placeholder->replace('string_WEBSITE_ID', ['NON_WEBSITE_ID' => 1])
        );
    }
}
