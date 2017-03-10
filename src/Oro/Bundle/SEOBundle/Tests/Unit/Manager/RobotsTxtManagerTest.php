<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Manager;

use Oro\Bundle\SEOBundle\Exception\RobotsTxtManagerException;
use Oro\Bundle\SEOBundle\Manager\RobotsTxtManager;
use Oro\Bundle\SEOBundle\Model\Exception\InvalidArgumentException;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

class RobotsTxtManagerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $path;

    /**
     * @var RobotsTxtManager
     */
    private $manager;

    protected function setUp()
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->filesystem = new Filesystem();
        $this->path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . time();
        $this->manager = new RobotsTxtManager(
            $this->logger,
            $this->filesystem,
            $this->path
        );
    }

    protected function tearDown()
    {
        $this->filesystem->remove($this->path);
    }

    public function testAddKeywordWithUnsupportedKeyword()
    {
        $value = 'Some value';
        $keyword = 'Some unsupported keyword';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Unsupported keyword: %s, supported keywords are: %s',
            $keyword,
            implode(', ', RobotsTxtManager::getAvailableKeywords())
        ));
        $this->manager->addKeyword($keyword, $value);
    }

    public function testRemoveKeywordWithUnsupportedKeyword()
    {
        $keyword = 'Some unsupported keyword';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Unsupported keyword: %s, supported keywords are: %s',
            $keyword,
            implode(', ', RobotsTxtManager::getAvailableKeywords())
        ));
        $this->manager->removeByKeyword($keyword);
    }

    public function testAddKeywordWhenThrowsExceptionDuringDump()
    {
        $value = 'Some value';
        $anotherPath = '/some_another_path';
        $this->manager = new RobotsTxtManager(
            $this->logger,
            $this->filesystem,
            $anotherPath
        );
        $filename = implode(DIRECTORY_SEPARATOR, [$anotherPath, RobotsTxtManager::ROBOTS_TXT_FILENAME]);
        $this->expectException(RobotsTxtManagerException::class);
        $message = sprintf('An error occurred while writing robots.txt file to %s', $filename);
        $this->logger->expects($this->once())
            ->method('error')
            ->with($message);
        $this->expectExceptionMessage($message);
        $this->manager->addKeyword(RobotsTxtManager::KEYWORD_SITEMAP, $value);
    }

    public function testAddKeywordIfFileNotExist()
    {
        $value = 'Some value';
        $filename = $this->getFilename();
        $content = sprintf('%s: %s', RobotsTxtManager::KEYWORD_SITEMAP, $value);

        $this->manager->addKeyword(RobotsTxtManager::KEYWORD_SITEMAP, $value);
        $this->assertFileExists($filename);
        $this->assertStringEqualsFile($filename, $content);
    }

    public function testRemoveKeywordIfFileNotExist()
    {
        $filename = $this->getFilename();

        $this->manager->removeByKeyword(RobotsTxtManager::KEYWORD_SITEMAP);
        $this->assertFileNotExists($filename);
    }

    public function testAddKeywordAndRemoveIfFileExist()
    {
        $value = 'Some value';
        $existingData = 'Some existing data';
        $filename = $this->getFilename();
        $content = sprintf('%s%s%s: %s', $existingData, PHP_EOL, RobotsTxtManager::KEYWORD_SITEMAP, $value);

        $this->filesystem->dumpFile($filename, $existingData);

        $this->manager->addKeyword(RobotsTxtManager::KEYWORD_SITEMAP, $value);
        $this->assertFileExists($filename);
        $this->assertStringEqualsFile($filename, $content);

        $this->manager->removeByKeyword(RobotsTxtManager::KEYWORD_SITEMAP);
        $this->assertFileExists($filename);
        $this->assertStringEqualsFile($filename, $existingData);
    }

    /**
     * @dataProvider changeKeywordProvider
     * @param string $value
     * @param string $keyword
     * @param string $existingData
     * @param string $expectedContent
     */
    public function testChangeKeyword($value, $keyword, $existingData, $expectedContent)
    {
        $filename = $this->getFilename();

        $this->filesystem->dumpFile($filename, $existingData);

        $this->manager->changeByKeyword($keyword, $value);
        $this->assertFileExists($filename);
        $this->assertStringEqualsFile($filename, $expectedContent);
    }

    /**
     * @return array
     */
    public function changeKeywordProvider()
    {
        $text = <<<TXT
Sitemap: value
Something: interesting
Other: not that interesting
TXT;
        $expectedText = <<<TXT
Something: interesting
Other: not that interesting
Sitemap: New value
TXT;

        return [
            'multiline' => [
                'value' => 'New value',
                'keyword' => RobotsTxtManager::KEYWORD_SITEMAP,
                'existingData' => $text,
                'expectedData' => $expectedText
            ],
            'when data exist' => [
                'value' => 'Some value',
                'keyword' => RobotsTxtManager::KEYWORD_SITEMAP,
                'existingData' => sprintf('%s: %s', RobotsTxtManager::KEYWORD_SITEMAP, 'Some existing value'),
                'expectedData' => sprintf('%s: %s', RobotsTxtManager::KEYWORD_SITEMAP, 'Some value'),
            ],
            'when data not exist' => [
                'value' => 'Some value',
                'keyword' => RobotsTxtManager::KEYWORD_SITEMAP,
                'existingData' => '',
                'expectedData' => sprintf('%s: %s', RobotsTxtManager::KEYWORD_SITEMAP, 'Some value'),
            ],
        ];
    }

    public function testIsSupportedKeywordFalse()
    {
        $this->assertFalse($this->manager->isSupportedKeyword('Some keyword'));
    }

    public function testIsSupportedKeyword()
    {
        $this->assertTrue($this->manager->isSupportedKeyword(RobotsTxtManager::KEYWORD_SITEMAP));
    }

    /**
     * @return string
     */
    private function getFilename()
    {
        return implode(DIRECTORY_SEPARATOR, [$this->path, RobotsTxtManager::ROBOTS_TXT_FILENAME]);
    }
}
