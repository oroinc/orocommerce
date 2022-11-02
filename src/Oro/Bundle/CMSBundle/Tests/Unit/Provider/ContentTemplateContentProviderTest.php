<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Provider;

use Oro\Bundle\CMSBundle\Provider\ContentTemplateContentProvider;
use Oro\Bundle\CMSBundle\Tests\Unit\Entity\Stub\ContentTemplateStub;
use Oro\Bundle\CMSBundle\Tools\DigitalAssetTwigTagsConverter;

class ContentTemplateContentProviderTest extends \PHPUnit\Framework\TestCase
{
    private ContentTemplateContentProvider $provider;

    protected function setUp(): void
    {
        $digitalAssetTwigTagsConverter = $this->createMock(DigitalAssetTwigTagsConverter::class);
        $digitalAssetTwigTagsConverter->expects(self::any())
            ->method('convertToUrls')
            ->willReturnCallback(static fn (string $content) => $content . '[converted]');

        $this->provider = new ContentTemplateContentProvider($digitalAssetTwigTagsConverter);
    }

    /**
     * @dataProvider getContentDataProvider
     */
    public function testGetContent(
        ?string $content,
        ?string $contentStyle,
        ?array $contentProperties,
        array $expectedResult
    ): void {
        $contentTemplate = (new ContentTemplateStub())
            ->setId(1)
            ->setContent($content)
            ->setContentStyle($contentStyle)
            ->setContentProperties($contentProperties);

        self::assertSame(
            $expectedResult,
            $this->provider->getContent($contentTemplate)
        );
    }

    public function getContentDataProvider(): array
    {
        $content = '<div class="one-column"><h3>Marguerite Fox</h3><p class="extra-text">Position</p></div>';
        $contentStyle = '.one-column .extra-text {padding: 20px 0px;}';
        $contentProperties = ['propFoo' => 'valueFoo', 'propBar' => 'valueBar'];

        return [
            'null' => [
                'content' => null,
                'contentStyle' => null,
                'contentProperties' => null,
                'expectedResult' => [
                    'content' => '',
                    'contentStyle' => '',
                    'contentProperties' => [],
                ],
            ],
            'empty strings' => [
                'content' => '',
                'contentStyle' => '',
                'contentProperties' => [],
                'expectedResult' => [
                    'content' => '',
                    'contentStyle' => '',
                    'contentProperties' => [],
                ],
            ],
            'content' => [
                'content' => $content,
                'contentStyle' => $contentStyle,
                'contentProperties' => $contentProperties,
                'expectedResult' => [
                    'content' => $content . '[converted]',
                    'contentStyle' => $contentStyle . '[converted]',
                    'contentProperties' => $contentProperties,
                ],
            ],
        ];
    }
}
