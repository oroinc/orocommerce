<?php

namespace Oro\Bundle\WebCatalogBundle\ContentNodeUtils;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Component\WebCatalog\ChainContentVariantTitleProvider;

class ContentNodeNameFiller
{
    /**
     * @var ChainContentVariantTitleProvider
     */
    protected $contentVariantTitleProvider;

    /**
     * @param ChainContentVariantTitleProvider $contentVariantTitleProvider
     */
    public function __construct(ChainContentVariantTitleProvider $contentVariantTitleProvider)
    {
        $this->contentVariantTitleProvider = $contentVariantTitleProvider;
    }

    /**
     * @param ContentNode $contentNode
     */
    public function fillName(ContentNode $contentNode)
    {
        if (!$this->isEmptyString($contentNode->getName())) {
            return;
        }

        $title = null;
        if ($contentNode->getDefaultTitle() instanceof LocalizedFallbackValue) {
            $title = $contentNode->getDefaultTitle()->getString();
        }
        if ($this->isEmptyString($title)) {
            foreach ($contentNode->getTitles() as $localizedTitle) {
                if (!$this->isEmptyString($localizedTitle->getString())) {
                    $title = $localizedTitle->getString();
                    break;
                }
            }
        }

        if ($this->isEmptyString($title)) {
            $title = $this->contentVariantTitleProvider->getFirstTitle($contentNode->getContentVariants());
        }

        $contentNode->setName($title);
    }

    /**
     * @param string $string
     * @return bool
     */
    protected function isEmptyString($string)
    {
        return $string === null || $string === '';
    }
}
