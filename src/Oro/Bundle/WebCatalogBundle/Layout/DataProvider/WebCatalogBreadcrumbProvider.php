<?php

namespace Oro\Bundle\WebCatalogBundle\Layout\DataProvider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\DependencyInjection\Configuration as CatalogConfiguration;
use Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryBreadcrumbProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration as ProductConfiguration;
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

    private ?ConfigManager $configManager = null;

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

    public function setConfigManager(ConfigManager $configManager): void
    {
        $this->configManager = $configManager;
    }

    /**
     * @return array
     */
    public function getItems()
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return [];
        }

        $contentVariant = $this->requestWebContentVariantProvider->getContentVariant();
        if ($contentVariant) {
            $breadcrumbs = $this->getItemsByContentVariant($contentVariant, $request);

            if ($this->shouldBreadcrumbsBeEmpty($breadcrumbs)) {
                return [];
            }

            if ($this->configManager?->get(
                CatalogConfiguration::getConfigKeyByName(CatalogConfiguration::EXCLUDE_CURRENT_BREADCRUMB_ON_ALL_PAGES)
            )) {
                array_pop($breadcrumbs);
            }

            return $breadcrumbs;
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
        if (!$request) {
            return [];
        }

        $contentVariant = $this->requestWebContentVariantProvider->getContentVariant();
        if ($contentVariant) {
            $breadcrumbs = $this->getItemsByContentVariant($contentVariant, $request);

            if ($this->shouldBreadcrumbsBeEmpty($breadcrumbs)) {
                return [];
            }

            if ($this->configManager?->get(
                ProductConfiguration::getConfigKeyByName(
                    ProductConfiguration::EXCLUDE_CURRENT_BREADCRUMB_ON_PRODUCT_VIEW
                )
            )) {
                array_pop($breadcrumbs);
            }

            return $breadcrumbs;
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

        if ($this->shouldBreadcrumbsBeEmpty($breadcrumbs)) {
            return [];
        }

        if ($this->configManager?->get(
            ProductConfiguration::getConfigKeyByName(
                ProductConfiguration::EXCLUDE_CURRENT_BREADCRUMB_ON_PRODUCT_VIEW
            )
        )) {
            array_pop($breadcrumbs);
        }

        return $breadcrumbs;
    }

    private function shouldBreadcrumbsBeEmpty(array $breadcrumbs): bool
    {
        return count($breadcrumbs) === 1 && $this->configManager?->get(
            CatalogConfiguration::getConfigKeyByName(CatalogConfiguration::REMOVE_SINGLE_BREADCRUMB)
        );
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
