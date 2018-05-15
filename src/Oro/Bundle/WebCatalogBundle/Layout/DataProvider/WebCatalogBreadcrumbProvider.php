<?php

namespace Oro\Bundle\WebCatalogBundle\Layout\DataProvider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryBreadcrumbProvider;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentVariantRepository;
use Oro\Component\WebCatalog\Entity\ContentNodeAwareInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class WebCatalogBreadcrumbProvider extends AbstractWebCatalogDataProvider
{
    /**
     * @var CategoryBreadcrumbProvider
     */
    private $categoryBreadcrumbProvider;

    /**
     * @param ManagerRegistry $registry
     * @param LocalizationHelper $localizationHelper
     * @param RequestStack $requestStack
     * @param CategoryBreadcrumbProvider $categoryBreadcrumbProvider
     */
    public function __construct(
        ManagerRegistry $registry,
        LocalizationHelper $localizationHelper,
        RequestStack $requestStack,
        CategoryBreadcrumbProvider $categoryBreadcrumbProvider
    ) {
        $this->registry = $registry;
        $this->localizationHelper = $localizationHelper;
        $this->requestStack = $requestStack;
        $this->categoryBreadcrumbProvider = $categoryBreadcrumbProvider;
    }

    /**
     * @return array
     */
    public function getItems()
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request && $contentVariant = $request->attributes->get('_content_variant')) {
            $breadcrumbs = $this->getItemsByContentVariant($contentVariant);
        } else {
            $breadcrumbs = $request->query->get('categoryId') ? $this->categoryBreadcrumbProvider->getItems() : [];
        }

        return $breadcrumbs;
    }

    /**
     * @param int    $categoryId
     * @param string $currentPageTitle
     *
     * @return array
     */
    public function getItemsForProduct($categoryId, $currentPageTitle)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return [];
        }
        if ($request->attributes->get('_content_variant')) {
            return $this->getItems();
        }
        $contextUrlAttributes = $request->attributes->get('_context_url_attributes');
        if (!$contextUrlAttributes) {
            return $this->categoryBreadcrumbProvider
                ->getItemsForProduct($categoryId, $currentPageTitle);
        }
        $breadcrumbs = [];
        $slug = isset($contextUrlAttributes[0]['_used_slug']) ? $contextUrlAttributes[0]['_used_slug'] : null;
        if ($slug) {
            $contentVariant = $this->getRepository()->findVariantBySlug($slug);
            $breadcrumbs = $this->getItemsByContentVariant($contentVariant);
        }
        $breadcrumbs[] = ['label' => $currentPageTitle, 'url' => null];

        return $breadcrumbs;
    }


    /**
     * Get breadcrumbs by content variant
     *
     * @param ContentNodeAwareInterface|null $contentVariant
     *
     * @return array
     */
    private function getItemsByContentVariant(ContentNodeAwareInterface $contentVariant = null)
    {
        $breadcrumbs = [];

        if ($contentVariant) {
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
     * @return ContentVariantRepository
     */
    private function getRepository()
    {
        return $this->registry
            ->getManagerForClass(ContentVariant::class)
            ->getRepository(ContentVariant::class);
    }
}
