<?php

namespace Oro\Bundle\WebCatalogBundle\Layout\DataProvider;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\WebCatalogBundle\Provider\RequestWebContentVariantProvider;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;

/**
 * Layout data provider. Returns web catalog node title and page title based on current content node.
 * Return default title in case if node title don't exist.
 */
class TitleDataProvider implements TitleDataProviderInterface
{
    /** @var RequestWebContentVariantProvider */
    private $requestWebContentVariantProvider;

    /** @var LocalizationHelper */
    private $localizationHelper;

    public function __construct(
        RequestWebContentVariantProvider $requestWebContentVariantProvider,
        LocalizationHelper $localizationHelper
    ) {
        $this->requestWebContentVariantProvider = $requestWebContentVariantProvider;
        $this->localizationHelper = $localizationHelper;
    }

    #[\Override]
    public function getNodeTitle($default = '')
    {
        return $this->getTitle($default);
    }

    #[\Override]
    public function getTitle($default = '', $data = null)
    {
        $contentNode = $this->getContentNode();
        if ($contentNode && $contentNode->isRewriteVariantTitle()) {
            $title = $this->localizationHelper->getLocalizedValue($contentNode->getTitles());
            if ($title !== null && $title !== '') {
                return $title;
            }
        }

        return $default;
    }

    #[\Override]
    public function isRenderTitle(): bool
    {
        $contentVariant = $this->requestWebContentVariantProvider->getContentVariant();

        return !$contentVariant?->isDoNotRenderTitle();
    }

    /**
     * @return ContentNodeInterface|null
     */
    private function getContentNode()
    {
        $contentVariant = $this->requestWebContentVariantProvider->getContentVariant();

        return null !== $contentVariant
            ? $contentVariant->getNode()
            : null;
    }
}
