<?php

namespace Oro\Bundle\WebCatalogBundle\Layout\DataProvider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryBreadcrumbProvider;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentVariantRepository;
use Oro\Bundle\WebCatalogBundle\Provider\RequestWebContentVariantProvider;
use Oro\Component\WebCatalog\Entity\ContentNodeAwareInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns breadcrumb items.
 */
class WebCatalogBreadcrumbProvider
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var LocalizationHelper */
    private $localizationHelper;

    /** @var RequestStack */
    private $requestStack;

    /** @var RequestWebContentVariantProvider */
    private $requestWebContentVariantProvider;

    /** @var CategoryBreadcrumbProvider */
    private $categoryBreadcrumbProvider;

    public function __construct(
        ManagerRegistry $doctrine,
        LocalizationHelper $localizationHelper,
        RequestStack $requestStack,
        RequestWebContentVariantProvider $requestWebContentVariantProvider,
        CategoryBreadcrumbProvider $categoryBreadcrumbProvider
    ) {
        $this->doctrine = $doctrine;
        $this->localizationHelper = $localizationHelper;
        $this->requestStack = $requestStack;
        $this->requestWebContentVariantProvider = $requestWebContentVariantProvider;
        $this->categoryBreadcrumbProvider = $categoryBreadcrumbProvider;
    }

    /**
     * @return array
     */
    public function getItems()
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null !== $request) {
            $contentVariant = $this->requestWebContentVariantProvider->getContentVariant();
            if (null !== $contentVariant) {
                return $this->getItemsByContentVariant($contentVariant, $request);
            }
        }

        return $request->query->get('categoryId')
            ? $this->categoryBreadcrumbProvider->getItems()
            : [];
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
        if (null === $request) {
            return [];
        }

        $contentVariant = $this->requestWebContentVariantProvider->getContentVariant();
        if (null !== $contentVariant) {
            return $this->getItems();
        }

        $contextUrlAttributes = $request->attributes->get('_context_url_attributes');
        if (!$contextUrlAttributes) {
            return $this->categoryBreadcrumbProvider->getItemsForProduct($categoryId, $currentPageTitle);
        }

        $breadcrumbs = [];
        $slug = $contextUrlAttributes[0]['_used_slug'] ?? null;
        if ($slug) {
            $contentVariant = $this->getContentVariantRepository()->findVariantBySlug($slug);
            $breadcrumbs = $this->getItemsByContentVariant($contentVariant, $request);
        }
        $breadcrumbs[] = ['label' => $currentPageTitle, 'url' => null];

        return $breadcrumbs;
    }

    /**
     * Get breadcrumbs by content variant
     *
     * @param ContentNodeAwareInterface|null $contentVariant
     * @param Request $request
     *
     * @return array
     */
    private function getItemsByContentVariant(?ContentNodeAwareInterface $contentVariant, Request $request)
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
                        'url' => $request->getBaseUrl() . (string)$this->localizationHelper
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
    private function getContentVariantRepository()
    {
        return $this->doctrine->getRepository(ContentVariant::class);
    }

    /**
     * @return ContentNodeRepository
     */
    private function getContentNodeRepository()
    {
        return $this->doctrine->getRepository(ContentNode::class);
    }
}
