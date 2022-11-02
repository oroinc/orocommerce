<?php

namespace Oro\Bundle\CMSBundle\Provider;

use Oro\Bundle\CMSBundle\Entity\ContentTemplate;
use Oro\Bundle\CMSBundle\Tools\DigitalAssetTwigTagsConverter;

/**
 * Provides the content of the specified ContentTemplate ready for use in WYSIWYG.
 */
class ContentTemplateContentProvider
{
    private DigitalAssetTwigTagsConverter $digitalAssetTwigTagsConverter;

    public function __construct(DigitalAssetTwigTagsConverter $digitalAssetTwigTagsConverter)
    {
        $this->digitalAssetTwigTagsConverter = $digitalAssetTwigTagsConverter;
    }

    public function getContent(ContentTemplate $contentTemplate): array
    {
        $content = (string)$contentTemplate->getContent();
        $contentStyle = (string)$contentTemplate->getContentStyle();

        return [
            'content' => $content ? $this->digitalAssetTwigTagsConverter->convertToUrls($content) : '',
            'contentStyle' => $contentStyle ? $this->digitalAssetTwigTagsConverter->convertToUrls($contentStyle) : '',
            'contentProperties' => (array) $contentTemplate->getContentProperties(),
        ];
    }
}
