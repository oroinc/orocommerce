<?php

namespace Oro\Bundle\WebsiteSearchTermBundle\Tests\Unit\Formatter;

use Oro\Bundle\WebsiteSearchTermBundle\Formatter\SearchTermPhrasesFormatter;
use PHPUnit\Framework\TestCase;

class SearchTermPhrasesFormatterTest extends TestCase
{
    private SearchTermPhrasesFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new SearchTermPhrasesFormatter(',');
    }

    /**
     * @dataProvider getFormatPhrasesToArrayDataProvider
     */
    public function testFormatPhrasesToArray(string $phrases, array $expectedResult): void
    {
        self::assertEquals(
            $expectedResult,
            $this->formatter->formatPhrasesToArray($phrases)
        );
    }

    public function getFormatPhrasesToArrayDataProvider(): array
    {
        return [
            [
                'phrases' => '',
                'expectedResult' => [],
            ],
            [
                'phrases' => 'foo',
                'expectedResult' => ['foo'],
            ],
            [
                'phrases' => 'foo,bar',
                'expectedResult' => ['foo','bar'],
            ],
        ];
    }
}
