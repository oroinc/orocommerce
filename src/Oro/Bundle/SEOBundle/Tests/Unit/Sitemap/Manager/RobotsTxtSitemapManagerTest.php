<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Sitemap\Manager;

use Oro\Bundle\SEOBundle\Manager\RobotsTxtFileManager;
use Oro\Bundle\SEOBundle\Sitemap\Manager\RobotsTxtSitemapManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class RobotsTxtSitemapManagerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var RobotsTxtFileManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $fileManager;

    /**
     * @var RobotsTxtSitemapManager
     */
    private $manager;

    protected function setUp(): void
    {
        $this->fileManager = $this->getMockBuilder(RobotsTxtFileManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->manager = new RobotsTxtSitemapManager($this->fileManager);
    }

    public function testAddSitemap()
    {
        $reflection = new \ReflectionProperty(get_class($this->manager), 'newSitemaps');
        $reflection->setAccessible(true);
        $reflection->setValue($this->manager, ['sitemap1', 'sitemap2']);

        $this->manager->addSitemap('sitemap1');
        $this->manager->addSitemap('sitemap3');

        $this->assertEquals(['sitemap1', 'sitemap2', 'sitemap3'], $reflection->getValue($this->manager));
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
        $reflection = new \ReflectionClass(get_class($this->manager));

        $reflectionContent = $reflection->getProperty('content');
        $reflectionContent->setAccessible(true);
        $this->assertEmpty($reflectionContent->getValue($this->manager));

        $reflectionContent = $reflection->getProperty('newSitemaps');
        $reflectionContent->setAccessible(true);
        $this->assertEmpty($reflectionContent->getValue($this->manager));

        $reflectionContent = $reflection->getProperty('existingSitemaps');
        $reflectionContent->setAccessible(true);
        $this->assertEmpty($reflectionContent->getValue($this->manager));
    }
}
