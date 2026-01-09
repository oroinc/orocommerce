<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Manager;

use Oro\Bundle\SEOBundle\Manager\RobotsTxtDistTemplateManager;
use Oro\Bundle\SEOBundle\Manager\RobotsTxtFileManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Website\WebsiteInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RobotsTxtDistTemplateManagerTest extends TestCase
{
    private const DEFAULT_CONTENT = <<<TEXT
# www.robotstxt.org/
# www.google.com/support/webmasters/bin/answer.py?hl=en&answer=156449

User-agent: *

TEXT;

    private WebsiteInterface|MockObject $website;
    private RobotsTxtFileManager|MockObject $robotsTxtFileManager;
    private RobotsTxtDistTemplateManager $robotsTxtDistTemplateManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->website = new Website();
        $this->robotsTxtFileManager = $this->createMock(RobotsTxtFileManager::class);

        $this->robotsTxtDistTemplateManager = new RobotsTxtDistTemplateManager(
            $this->robotsTxtFileManager,
            __DIR__ . '/fixtures/'
        );
    }

    /**
     * @dataProvider getIsTemplateFileExistWithExistingDomainDistFileDataProvider
     */
    public function testIsTemplateFileExistWithExistingDomainDistFile(string $fileName, bool $expectedResult): void
    {
        $this->robotsTxtFileManager->expects(self::once())
            ->method('getFileNameByWebsite')
            ->with($this->website)
            ->willReturn($fileName);

        self::assertEquals(
            $expectedResult,
            $this->robotsTxtDistTemplateManager->isDistTemplateFileExist($this->website)
        );
    }

    public function getIsTemplateFileExistWithExistingDomainDistFileDataProvider(): array
    {
        return [
            'file is exists' => [
                'fileName' => 'robots.domain.com.txt',
                'expectedResult' => true,
            ],
            'file does not exist' => [
                'fileName' => 'robots.another.com.txt',
                'expectedResult' => false,
            ],
        ];
    }

    public function testGetTemplateContentWithExistingDomainDistFile(): void
    {
        $this->robotsTxtFileManager->expects(self::once())
            ->method('getFileNameByWebsite')
            ->with($this->website)
            ->willReturn('robots.domain.com.txt');

        self::assertEquals(
            "#test domain robots file\n",
            $this->robotsTxtDistTemplateManager->getDistTemplateContent($this->website)
        );
    }

    public function testGetTemplateContentWithExistingDefaultDistFile(): void
    {
        $this->robotsTxtFileManager->expects(self::once())
            ->method('getFileNameByWebsite')
            ->with($this->website)
            ->willReturn('robots.another.com.txt');

        self::assertEquals(
            "#test robots file\n",
            $this->robotsTxtDistTemplateManager->getDistTemplateContent($this->website)
        );
    }

    public function testGetTemplateContentWithNotExistingDefaultFile(): void
    {
        $robotsTxtDistTemplateManager = new RobotsTxtDistTemplateManager(
            $this->robotsTxtFileManager,
            '/not_existing_path/'
        );

        self::assertEquals(
            self::DEFAULT_CONTENT,
            $robotsTxtDistTemplateManager->getDistTemplateContent()
        );
    }

    public function testGetTemplateContentWithNotExistingFileDomainAndDefaultFiles(): void
    {
        $robotsTxtDistTemplateManager = new RobotsTxtDistTemplateManager(
            $this->robotsTxtFileManager,
            '/not_existing_path/'
        );

        $this->robotsTxtFileManager->expects(self::once())
            ->method('getFileNameByWebsite')
            ->with($this->website)
            ->willReturn('robots.another.com.txt');

        self::assertEquals(
            self::DEFAULT_CONTENT,
            $robotsTxtDistTemplateManager->getDistTemplateContent($this->website)
        );
    }
}
