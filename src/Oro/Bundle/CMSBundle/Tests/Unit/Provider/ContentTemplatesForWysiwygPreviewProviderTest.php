<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Provider\PictureSourcesProviderInterface;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestFile;
use Oro\Bundle\CMSBundle\Entity\ContentTemplate;
use Oro\Bundle\CMSBundle\Entity\Repository\ContentTemplateRepository;
use Oro\Bundle\CMSBundle\Provider\ContentTemplatesForWysiwygPreviewProvider;
use Oro\Bundle\CMSBundle\Tests\Unit\Entity\Stub\ContentTemplateStub;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;

class ContentTemplatesForWysiwygPreviewProviderTest extends \PHPUnit\Framework\TestCase
{
    private const PREVIEW_IMAGE_MEDIUM_SOURCES = [
        'src' => '/url/for/medium/image.png',
        'sources' => [
            [
                'srcset' => '/url/for/formatted/medium/image.jpg',
                'type' => 'image/jpg',
            ],
        ],
    ];

    private const PREVIEW_IMAGE_ORIGINAL_SOURCES = [
        'src' => '/url/for/original/image.png',
        'sources' => [
            [
                'srcset' => '/url/for/formatted/original/image.jpg',
                'type' => 'image/jpg',
            ],
        ],
    ];

    private const PLACEHOLDER = 'placeholder/image.png';

    private PictureSourcesProviderInterface|\PHPUnit\Framework\MockObject\MockObject $pictureSourcesProvider;

    private ContentTemplateRepository|\PHPUnit\Framework\MockObject\MockObject $contentTemplateRepository;

    private ContentTemplatesForWysiwygPreviewProvider $provider;

    protected function setUp(): void
    {
        $this->pictureSourcesProvider = $this->createMock(PictureSourcesProviderInterface::class);
        $this->contentTemplateRepository = $this->createMock(ContentTemplateRepository::class);
        $imagePlaceholderProvider = $this->createMock(ImagePlaceholderProviderInterface::class);
        $imagePlaceholderProvider->expects(self::any())
            ->method('getPath')
            ->willReturnCallback(static function (string $filter, string $format) {
                return '/' . $filter . '/' . self::PLACEHOLDER . ($format ? '.' . $format : '');
            });

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(ContentTemplate::class)
            ->willReturn($this->contentTemplateRepository);

        $this->provider = new ContentTemplatesForWysiwygPreviewProvider(
            $doctrine,
            $this->pictureSourcesProvider,
            $imagePlaceholderProvider
        );
    }

    public function testGetContentTemplatesListEmptyResults(): void
    {
        $this->pictureSourcesProvider->expects(self::never())
            ->method('getFilteredPictureSources')
            ->withAnyParameters();

        $this->contentTemplateRepository->expects(self::once())
            ->method('findContentTemplatesByTags')
            ->willReturn([]);

        self::assertEquals([], $this->provider->getContentTemplatesList());
    }

    public function testGetContentTemplatesList(): void
    {
        $previewImageFoo = (new TestFile())->setId(1001);

        $this->pictureSourcesProvider->expects(self::exactly(4))
            ->method('getFilteredPictureSources')
            ->willReturnMap([
                [
                    $previewImageFoo,
                    'content_template_preview_medium',
                    self::PREVIEW_IMAGE_MEDIUM_SOURCES
                ],
                [
                    $previewImageFoo,
                    'content_template_preview_original',
                    self::PREVIEW_IMAGE_ORIGINAL_SOURCES
                ],
            ]);

        $contentTemplates = [
            [
                'template' => (new ContentTemplateStub())
                    ->setId(1)
                    ->setName('Content Template 1')
                    ->setPreviewImage($previewImageFoo),
                'tags' => [],
            ],
            [
                'template' => (new ContentTemplateStub())
                    ->setId(2)
                    ->setName('Content Template 2'),
                'tags' => ['tag1', 'tag2'],
            ],
            [
                'template' => (new ContentTemplateStub())
                    ->setId(3)
                    ->setName('Content Template 3')
                    ->setPreviewImage($previewImageFoo),
                'tags' => ['tag1', 'tag2'],
            ],
        ];

        $this->contentTemplateRepository->expects(self::once())
            ->method('findContentTemplatesByTags')
            ->willReturn($contentTemplates);

        $expectedResults = [
            [
                'id' => 1,
                'name' => 'Content Template 1',
                'tags' => [],
                'previewImage' => [
                    'medium' => self::PREVIEW_IMAGE_MEDIUM_SOURCES,
                    'large' => self::PREVIEW_IMAGE_ORIGINAL_SOURCES,
                ],
            ],
            [
                'id' => 2,
                'name' => 'Content Template 2',
                'tags' => ['tag1', 'tag2'],
                'previewImage' => [
                    'medium' => [
                        'src' => '/content_template_preview_medium/placeholder/image.png',
                        'sources' => [],
                    ],
                    'large' => [
                        'src' => '/content_template_preview_original/placeholder/image.png',
                        'sources' => [],
                    ],
                ],
            ],
            [
                'id' => 3,
                'name' => 'Content Template 3',
                'tags' => ['tag1', 'tag2'],
                'previewImage' => [
                    'medium' => self::PREVIEW_IMAGE_MEDIUM_SOURCES,
                    'large' => self::PREVIEW_IMAGE_ORIGINAL_SOURCES,
                ],
            ],
        ];

        self::assertEquals($expectedResults, $this->provider->getContentTemplatesList());
    }
}
