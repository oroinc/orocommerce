<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\ContentNodeUtils\Factory;

use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\Factory\ResolvedContentNodeIdentifierGenerator;

class ResolvedContentNodeIdentifierGeneratorTest extends \PHPUnit\Framework\TestCase
{
    private ResolvedContentNodeIdentifierGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new ResolvedContentNodeIdentifierGenerator();
    }

    /**
     * @dataProvider getIdentifierByUrlDataProvider
     */
    public function testGetIdentifierByUrl(string $url, string $expected): void
    {
        self::assertEquals($expected, $this->generator->getIdentifierByUrl($url));
    }

    public function getIdentifierByUrlDataProvider(): array
    {
        return [
            'empty string' => ['url' => '', 'expected' => ''],
            'string without slashes' => ['url' => 'sample', 'expected' => 'root__sample'],
            'string with slash at the end' => ['url' => 'sample/', 'expected' => 'root__sample'],
            'string with slash at the start and end' => ['url' => '/sample/', 'expected' => 'root__sample'],
            'string with multiple parts' => [
                'url' => '/sample/part1/part2/',
                'expected' => 'root__sample__part1__part2',
            ],
        ];
    }
}
