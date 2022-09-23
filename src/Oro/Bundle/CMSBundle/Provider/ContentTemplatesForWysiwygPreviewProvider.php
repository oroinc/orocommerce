<?php

namespace Oro\Bundle\CMSBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\PictureSourcesProviderInterface;
use Oro\Bundle\CMSBundle\Entity\ContentTemplate;
use Oro\Bundle\CMSBundle\Entity\Repository\ContentTemplateRepository;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;
use Oro\Bundle\TagBundle\Entity\Tag;

/**
 * Provides content templates list for WYSIWYG
 */
class ContentTemplatesForWysiwygPreviewProvider
{
    protected const IMAGE_FILTER_MEDIUM = 'content_template_preview_medium';
    protected const IMAGE_FILTER_LARGE = 'content_template_preview_original';

    private ManagerRegistry $managerRegistry;

    private PictureSourcesProviderInterface $pictureSourcesProvider;

    private ImagePlaceholderProviderInterface $imagePlaceholderProvider;

    public function __construct(
        ManagerRegistry $managerRegistry,
        PictureSourcesProviderInterface $pictureSourcesProvider,
        ImagePlaceholderProviderInterface $imagePlaceholderProvider
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->pictureSourcesProvider = $pictureSourcesProvider;
        $this->imagePlaceholderProvider = $imagePlaceholderProvider;
    }

    /**
     * @param Tag[]|int[] $tags
     * @return array
     *  [
     *      [
     *          'id' => 1, // Content Template id
     *          'name' => 'One column. Text and images', // Content Template Name
     *          'tags = [ // Content Template tags
     *              "Testimonials",
     *          ],
     *          'previewImage' => [
     *              // Content Template preview image filter name
     *              'content_template_preview_original' => [ // Sources array that can be used in <picture> tag
     *                  'src' => '/url/for/original/image.png',
     *                  'sources' => [
     *                      [
     *                          'srcset' => '/url/for/formatted/image.jpg',
     *                          'type' => 'image/jpg',
     *                      ],
     *                      // ...
     *              ],
     *              // ...
     *          ],
     *      ],
     *     // ...
     *  ]
     */
    public function getContentTemplatesList(array $tags = []): array
    {
        $contentTemplatesList = [];
        $contentTemplatesData = $this->getRepository()->findContentTemplatesByTags($tags);
        foreach ($contentTemplatesData as $contentTemplateData) {
            $contentTemplatesList[] = [
                'id' => $contentTemplateData['template']->getId(),
                'name' => $contentTemplateData['template']->getName(),
                'tags' => $contentTemplateData['tags'],
                'previewImage' => $this->getPreviewData($contentTemplateData['template']->getPreviewImage()),
            ];
        }

        return $contentTemplatesList;
    }

    private function getPreviewData(?File $previewImage): array
    {
        if ($previewImage) {
            return [
                'medium' => $this->pictureSourcesProvider
                    ->getFilteredPictureSources($previewImage, static::IMAGE_FILTER_MEDIUM),
                'large' => $this->pictureSourcesProvider
                    ->getFilteredPictureSources($previewImage, static::IMAGE_FILTER_LARGE),
            ];
        }

        return [
            'medium' => [
                'src' => $this->imagePlaceholderProvider->getPath(static::IMAGE_FILTER_MEDIUM),
                'sources' => [],
            ],
            'large' => [
                'src' => $this->imagePlaceholderProvider->getPath(static::IMAGE_FILTER_LARGE),
                'sources' => [],
            ],
        ];
    }

    private function getRepository(): ContentTemplateRepository
    {
        return $this->managerRegistry->getRepository(ContentTemplate::class);
    }
}
