<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Sitemap\Manager;

use Oro\Bundle\SEOBundle\Manager\RobotsTxtFileManager;
use Oro\Bundle\SEOBundle\Sitemap\Manager\RobotsTxtSitemapManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\ReflectionUtil;

class RobotsTxtSitemapManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var RobotsTxtFileManager|\PHPUnit\Framework\MockObject\MockObject */
    private $fileManager;

    /** @var RobotsTxtSitemapManager */
    private $manager;

    protected function setUp(): void
    {
        $this->fileManager = $this->createMock(RobotsTxtFileManager::class);

        $this->manager = new RobotsTxtSitemapManager($this->fileManager);
    }

    public function testAddSitemap()
    {
        ReflectionUtil::setPropertyValue($this->manager, 'newSitemaps', ['sitemap1', 'sitemap2']);

        $this->manager->addSitemap('sitemap1');
        $this->manager->addSitemap('sitemap3');

        $this->assertEquals(
            ['sitemap1', 'sitemap2', 'sitemap3'],
            ReflectionUtil::getPropertyValue($this->manager, 'newSitemaps')
        );
    }

    public function testFlush()
    {
        $content = <<<EOF
# Some text
Sitemap : http://example.com/custom-sitemap1.xml
 Sitemap: http://example.com/custom-sitemap2.xml # with comment
 sitemap : http://example.com/custom-sitemap3.xml
# Sitemap : http://example.com/custom-sitemap4.xml
Sitemap: http://example.com/sitemap1.xml # auto-generated
Sitemap: http://example.com/sitemap2.xml # auto-generated
Allow: allowed_path
Allow: another_allowed_path
# Some text
EOF;

        $expectedContent = <<<EOF
# Some text
Sitemap : http://example.com/custom-sitemap1.xml
 Sitemap: http://example.com/custom-sitemap2.xml # with comment
 sitemap : http://example.com/custom-sitemap3.xml
# Sitemap : http://example.com/custom-sitemap4.xml
Sitemap: http://example.com/sitemap1.xml # auto-generated
Sitemap: http://example.com/sitemap2.xml # auto-generated
Allow: allowed_path
Allow: another_allowed_path
# Some text
Sitemap: http://example.com/sitemap3.xml # auto-generated
EOF;

        $this->fileManager->expects($this->once())
            ->method('getContent')
            ->willReturn($content);

        $this->manager->addSitemap('http://example.com/sitemap2.xml');
        $this->manager->addSitemap('http://example.com/sitemap3.xml');

        $this->fileManager->expects($this->once())
            ->method('dumpContent')
            ->with($expectedContent);

        $this->manager->flush(new Website());
        $this->checkClear();
    }

    private function checkClear()
    {
        $this->assertEmpty(ReflectionUtil::getPropertyValue($this->manager, 'content'));
        $this->assertEmpty(ReflectionUtil::getPropertyValue($this->manager, 'newSitemaps'));
        $this->assertEmpty(ReflectionUtil::getPropertyValue($this->manager, 'existingSitemaps'));
    }
}
