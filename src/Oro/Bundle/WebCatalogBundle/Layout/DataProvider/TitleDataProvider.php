<?php

namespace Oro\Bundle\WebCatalogBundle\Layout\DataProvider;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Component\WebCatalog\Entity\ContentNodeAwareInterface;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class TitleDataProvider
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var LocalizationHelper
     */
    protected $localizationHelper;

    /**
     * @param RequestStack $requestStack
     * @param LocalizationHelper $localizationHelper
     */
    public function __construct(
        RequestStack $requestStack,
        LocalizationHelper $localizationHelper
    ) {
        $this->requestStack = $requestStack;
        $this->localizationHelper = $localizationHelper;
    }

    /**
     * @param string $default
     * @return LocalizedFallbackValue|string
     */
    public function getTitle($default = '')
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

    /**
     * @return null|ContentNodeInterface
     */
    protected function getContentNode()
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request && $request->attributes->has('_content_variant')) {
            $contentVariant = $request->attributes->get('_content_variant');

            if ($contentVariant instanceof ContentNodeAwareInterface) {
                return $contentVariant->getNode();
            }
        }

        return null;
    }
}
