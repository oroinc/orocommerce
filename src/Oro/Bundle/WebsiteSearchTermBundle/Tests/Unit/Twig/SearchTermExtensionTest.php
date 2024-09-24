<?php

namespace Oro\Bundle\WebsiteSearchTermBundle\Tests\Unit\Twig;

use Oro\Bundle\WebsiteSearchTermBundle\Formatter\SearchTermPhrasesFormatter;
use Oro\Bundle\WebsiteSearchTermBundle\Twig\SearchTermExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\TestCase;

class SearchTermExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private SearchTermExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $formatter = new SearchTermPhrasesFormatter(',');

        $container = self::getContainerBuilder()
            ->add('oro_website_search_term.formatter.search_term_phrases_formatter', $formatter)
            ->getContainer($this);

        $this->extension = new SearchTermExtension($container);
    }

    /**
     * @dataProvider getFormatPhrasesDataProvider
     */
    public function testFormatPhrases(string $phrases, array $expectedResult): void
    {
        self::assertEquals(
            $expectedResult,
            self::callTwigFilter($this->extension, 'oro_format_search_term_phrases', [$phrases])
        );
    }

    public function getFormatPhrasesDataProvider(): array
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
                'expectedResult' => ['foo', 'bar'],
            ],
        ];
    }

    /**
     * @dataProvider getFormatPhrasesWithJoinDataProvider
     */
    public function testFormatPhrasesWithJoin(string $phrases, string $joinWith, string $expectedResult): void
    {
        self::assertEquals(
            $expectedResult,
            self::callTwigFilter($this->extension, 'oro_format_search_term_phrases', [$phrases, $joinWith])
        );
    }

    public function getFormatPhrasesWithJoinDataProvider(): array
    {
        return [
            [
                'phrases' => '',
                'joinWith' => '',
                'expectedResult' => '',
            ],
            [
                'phrases' => 'foo',
                'joinWith' => ', ',
                'expectedResult' => 'foo',
            ],
            [
                'phrases' => 'foo,bar',
                'joinWith' => ', ',
                'expectedResult' => 'foo, bar',
            ],
        ];
    }
}
