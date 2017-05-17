<?php

namespace Oro\Bundle\WebCatalogBundle\Layout\DataProvider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;

class WebCatalogBreadcrumbProvider extends AbstractWebCatalogDataProvider
{
    /**
     * @param ManagerRegistry $registry
     * @param LocalizationHelper $localizationHelper
     * @param RequestStack $requestStack
     */
    public function __construct(
        ManagerRegistry $registry,
        LocalizationHelper $localizationHelper,
        RequestStack $requestStack
    ) {
        $this->registry = $registry;
        $this->localizationHelper = $localizationHelper;
        $this->requestStack = $requestStack;
    }

    /**
     * @return array
     */
    public function getItems()
    {
        $breadcrumbs = [];
        $request = $this->requestStack->getCurrentRequest();

        if ($request && $contentVariant = $request->attributes->get('_content_variant')) {
            $contentNode = $contentVariant->getNode();
            $path = $this->getContentNodeRepository()->getPath($contentNode);

            if (is_array($path)) {
                foreach ($path as $breadcrumb) {
                    $breadcrumbs[] = [
                        'label' => (string)$this->localizationHelper
                            ->getLocalizedValue($breadcrumb->getTitles()),
                        'url' => (string)$this->localizationHelper
                                ->getLocalizedValue($breadcrumb->getLocalizedUrls())
                    ];
                }
            }
        }

        return $breadcrumbs;
    }

    /**
     * @param string $currentPageTitle
     *
     * @return array
     */
    public function getItemsForProduct($currentPageTitle)
    {
        $breadcrumbs = $this->getItems();
        $breadcrumbs[] = ['label' => $currentPageTitle, 'url' => null];

        return $breadcrumbs;
    }
}
