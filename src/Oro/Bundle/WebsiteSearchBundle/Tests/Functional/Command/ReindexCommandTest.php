<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Command;

use Oro\Bundle\SearchBundle\Tests\Functional\SearchExtensionTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexText;

class ReindexCommandTest extends WebTestCase
{
    use SearchExtensionTrait;

    protected function setUp(): void
    {
        $this->initClient();
    }

    protected function tearDown(): void
    {
        $this->clearIndexTextTable(IndexText::class);
    }

    public function testCommand()
    {
        $result = $this->runCommand(
            'oro:website-search:reindex',
            [
                '--website-id' => '123',
                '--class' => 'OroTestFrameworkBundle:TestProduct'
            ]
        );

        $expectedOutput = 'Starting reindex task for Oro\Bundle\TestFrameworkBundle\Entity\TestProduct ' .
            'and website ID 123...';
        static::assertStringContainsString($expectedOutput, $result);
        static::assertStringContainsString('Reindex finished successfully.', $result);
    }

    public function testImproperEntityIdCommand()
    {
        $result = $this->runCommand(
            'oro:website-search:reindex',
            [
                '--ids' => '*/1000',
                '--class' => 'OroTestFrameworkBundle:TestProduct'
            ]
        );

        $expectedOutput = 'Splitting entities makes only sense with --scheduled';
        static::assertStringContainsString($expectedOutput, $result);
    }

    public function testSyncRangeProductIdCommand()
    {
        $result = $this->runCommand(
            'oro:website-search:reindex',
            [
                '--ids' => '1-1000',
                '--class' => 'OroTestFrameworkBundle:TestProduct'
            ]
        );

        $expectedOutput = 'Starting reindex task for Oro\Bundle\TestFrameworkBundle\Entity\TestProduct';
        static::assertStringContainsString($expectedOutput, $result);
        static::assertStringContainsString('Generating indexation requests for an ID range of 1-1000...', $result);
        static::assertStringContainsString('Reindex finished successfully.', $result);
    }

    public function testSplitProductIdCommand()
    {
        $result = $this->runCommand(
            'oro:website-search:reindex',
            [
                '--ids' => '*/1000',
                '--scheduled' => true,
                '--class' => 'OroTestFrameworkBundle:TestProduct'
            ]
        );

        $expectedOutput = 'Starting reindex task for Oro\Bundle\TestFrameworkBundle\Entity\TestProduct';
        static::assertStringContainsString($expectedOutput, $result);
        static::assertStringContainsString('Generating indexation requests 1000 entities each...', $result);
        static::assertStringContainsString('Reindex finished successfully.', $result);
    }

    public function testRangeProductIdCommand()
    {
        $result = $this->runCommand(
            'oro:website-search:reindex',
            [
                '--ids' => '1-5000',
                '--scheduled' => true,
                '--class' => 'OroTestFrameworkBundle:TestProduct'
            ]
        );

        $expectedOutput = 'Starting reindex task for Oro\Bundle\TestFrameworkBundle\Entity\TestProduct';
        static::assertStringContainsString($expectedOutput, $result);
        static::assertStringContainsString('Generating indexation requests for an ID range of 1-5000...', $result);
        static::assertStringContainsString('Reindex finished successfully.', $result);
    }

    public function testRangeSplitProductIdCommand()
    {
        $result = $this->runCommand(
            'oro:website-search:reindex',
            [
                '--ids' => '1-5000/40',
                '--scheduled' => true,
                '--class' => 'OroTestFrameworkBundle:TestProduct'
            ]
        );

        $expectedOutput = 'Starting reindex task for Oro\Bundle\TestFrameworkBundle\Entity\TestProduct';
        static::assertStringContainsString($expectedOutput, $result);
        static::assertStringContainsString(
            'Generating indexation requests 40 entities each for an ID range of 1-5000...',
            $result
        );
        static::assertStringContainsString('Reindex finished successfully.', $result);
    }

    public function testEmptyClassForGivenIdsCommand()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('--class option is required when using --ids');

        $this->runCommand(
            'oro:website-search:reindex',
            [
                '--ids' => '*/1000',
            ],
            true,
            true
        );
    }
}
