<?php

namespace Oro\Bundle\WebCatalogBundle\ContentNodeUtils;

use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Component\WebCatalog\ChainContentVariantTitleProvider;

class ContentNodeNameFiller
{

    /**
     * @var ChainContentVariantTitleProvider
     */
    protected $contentVariantTitleProvider;

    public function __construct(ChainContentVariantTitleProvider $contentVariantTitleProvider)
    {
        $this->contentVariantTitleProvider = $contentVariantTitleProvider;
    }

    /**
     * @param ContentNode $contentNode
     */
    public function fillName(ContentNode $contentNode)
    {
        if ($contentNode->getName()) {
            return;
        }

        $title = null;
        if ($contentNode->getDefaultTitle() && $contentNode->getDefaultTitle()->getText()) {
            $title = $contentNode->getDefaultTitle()->getText();
        } else {
            $title = $this->contentVariantTitleProvider->getFirstTitle($contentNode->getContentVariants());
        }

        if (! empty($title)) {
            $contentNode->setName($title);
        }
    }
}
