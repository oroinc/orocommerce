<?php

namespace Oro\Bundle\WebCatalogBundle\Generator;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Generator\DTO\SlugUrl;
use Oro\Bundle\WebCatalogBundle\ContentVariantType\ContentVariantTypeRegistry;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Component\Routing\RouteData;

class SlugGenerator
{
    const ROOT_URL = '/';

    /**
     * @var ContentVariantTypeRegistry
     */
    protected $contentVariantTypeRegistry;

    /**
     * @param ContentVariantTypeRegistry $contentVariantTypeRegistry
     */
    public function __construct(ContentVariantTypeRegistry $contentVariantTypeRegistry)
    {
        $this->contentVariantTypeRegistry = $contentVariantTypeRegistry;
    }

    /**
     * @param ContentNode $contentNode
     */
    public function generate(ContentNode $contentNode)
    {
        if ($contentNode->getParentNode()) {
            $slugUrls = $this->prepareSlugUrls($contentNode);
        } else {
            // Slug url for root content node
            $slugUrls = new ArrayCollection([new SlugUrl(self::ROOT_URL)]);
        }

        $this->updateLocalizedUrls($contentNode, $slugUrls);
        $this->bindSlugs($contentNode, $slugUrls);

        if (!$contentNode->getChildNodes()->isEmpty()) {
            foreach ($contentNode->getChildNodes() as $childNode) {
                $this->generate($childNode);
            }
        }
    }

    /**
     * @param ContentNode $contentNode
     * @param SlugUrl[]|Collection $slugUrls
     */
    protected function bindSlugs(ContentNode $contentNode, Collection $slugUrls)
    {
        foreach ($contentNode->getContentVariants() as $contentVariant) {
            $contentVariantType = $this->contentVariantTypeRegistry->getContentVariantType($contentVariant->getType());
            $routeData = $contentVariantType->getRouteData($contentVariant);
            $scopes = $contentVariant->getScopes();

            $toRemove = [];
            foreach ($contentVariant->getSlugs() as $slug) {
                $localeId = (int)$this->getLocaleId($slug->getLocalization());
                if ($slugUrls->containsKey($localeId)) {
                    /** @var SlugUrl $slugUrl */
                    $slugUrl = $slugUrls->get($localeId);
                    $slug->resetScopes();
                    $this->fillSlug($slug, $slugUrl, $routeData, $scopes);
                } else {
                    $toRemove[] = $slug;
                }
            }
            foreach ($toRemove as $slugToRemove) {
                $contentVariant->removeSlug($slugToRemove);
            }

            foreach ($slugUrls as $slugUrl) {
                if (!$this->getExistingSlug($slugUrl, $contentVariant)) {
                    $slug = new Slug();
                    $this->fillSlug($slug, $slugUrl, $routeData, $scopes);
                    $contentVariant->addSlug($slug);
                }
            }
        }
    }

    /**
     * @param SlugUrl $slugUrl
     * @param ContentVariant $contentVariant
     * @return Slug|null
     */
    protected function getExistingSlug(SlugUrl $slugUrl, ContentVariant $contentVariant)
    {
        $existingSlugs = $contentVariant->getSlugs();

        foreach ($existingSlugs as $existingSlug) {
            if ($slugUrl->getUrl() === $existingSlug->getUrl()
                && $slugUrl->getLocalization() === $existingSlug->getLocalization()
            ) {
                return $existingSlug;
            }
        }

        return null;
    }

    /**
     * @param ContentNode $contentNode
     * @return SlugUrl[]|Collection
     */
    protected function prepareSlugUrls(ContentNode $contentNode)
    {
        $filledSlugPrototypes = $this->getFilledSlugPrototypes($contentNode);
        $parentNodeSlugUrls = $this->getParentNodeSlugUrls($contentNode);

        $slugUrls = new ArrayCollection();
        foreach ($filledSlugPrototypes as $localeId => $changedSlugPrototypeValue) {
            $slugPrototype = $changedSlugPrototypeValue->getUrl();
            $locale = $changedSlugPrototypeValue->getLocalization();

            $url = null;
            if (array_key_exists($localeId, $parentNodeSlugUrls)) {
                $url = $this->getUrl($parentNodeSlugUrls[$localeId], $slugPrototype);
            } elseif ($locale && $fallbackSlug = $this->findFallbackSlug($locale, $parentNodeSlugUrls)) {
                $url = $this->getUrl($fallbackSlug, $slugPrototype);
            }

            if (null !== $url) {
                $slugUrls->set((int)$this->getLocaleId($locale), new SlugUrl($url, $locale));
            }
        }

        return $slugUrls;
    }

    /**
     * @param string $parentUrl
     * @param string $slugPrototype
     * @return string
     */
    protected function getUrl($parentUrl, $slugPrototype)
    {
        $parentUrl = rtrim($parentUrl, Slug::DELIMITER);

        return $parentUrl . Slug::DELIMITER . $slugPrototype;
    }

    /**
     * @param ContentNode $contentNode
     * @return array|SlugUrl[]
     */
    protected function getParentNodeSlugUrls(ContentNode $contentNode)
    {
        $parentNode = $contentNode->getParentNode();

        $parentNodeSlugUrls = [];
        if ($parentNode) {
            foreach ($parentNode->getLocalizedUrls() as $parentNodeSlug) {
                $localeId = $this->getLocaleId($parentNodeSlug->getLocalization());
                $parentNodeSlugUrls[$localeId] = $parentNodeSlug->getText();
            }
        }

        return $parentNodeSlugUrls;
    }

    /**
     * @param ContentNode $contentNode
     * @return array|SlugUrl[]
     */
    protected function getFilledSlugPrototypes(ContentNode $contentNode)
    {
        $slugPrototypes = $contentNode->getSlugPrototypes();
        $changedSlugPrototypes = [];
        foreach ($slugPrototypes as $slugPrototype) {
            $value = $slugPrototype->getString();
            if ($value !== '' && $value !== null && !$slugPrototype->getFallback()) {
                $localeId = $this->getLocaleId($slugPrototype->getLocalization());

                $changedSlugPrototypes[$localeId] = new SlugUrl($value, $slugPrototype->getLocalization());
            }
        }

        return $changedSlugPrototypes;
    }

    /**
     * @param Localization $localization
     * @param array|SlugUrl[] $parentNodeSlugUrls
     * @return string|null
     */
    protected function findFallbackSlug(Localization $localization, array $parentNodeSlugUrls)
    {
        $localeHierarchy = $localization->getHierarchy();

        foreach ($localeHierarchy as $localeId) {
            if (array_key_exists($localeId, $parentNodeSlugUrls)) {
                return $parentNodeSlugUrls[$localeId];
            }
        }

        return null;
    }

    /**
     * @param Localization|null $localization
     * @return int|null
     */
    protected function getLocaleId(Localization $localization = null)
    {
        if ($localization) {
            return $localization->getId();
        }

        return null;
    }

    /**
     * @param Slug $slug
     * @param SlugUrl $slugUrl
     * @param RouteData $routeData
     * @param Collection $scopes
     */
    protected function fillSlug(Slug $slug, SlugUrl $slugUrl, RouteData $routeData, Collection $scopes)
    {
        $slug->setLocalization($slugUrl->getLocalization());
        $slug->setUrl($slugUrl->getUrl());
        $slug->setRouteName($routeData->getRoute());
        $slug->setRouteParameters($routeData->getRouteParameters());
        foreach ($scopes as $scope) {
            $slug->addScope($scope);
        }
    }

    /**
     * @param SlugUrl $slugUrl
     * @param ContentNode $contentNode
     * @return LocalizedFallbackValue|null
     */
    protected function getExistingLocalizedUrl(SlugUrl $slugUrl, ContentNode $contentNode)
    {
        foreach ($contentNode->getLocalizedUrls() as $localizedUrl) {
            if ($slugUrl->getUrl() === $localizedUrl->getText()
                && $slugUrl->getLocalization() === $localizedUrl->getLocalization()
            ) {
                return $localizedUrl;
            }
        }

        return null;
    }

    /**
     * @param ContentNode $contentNode
     * @param Collection $slugUrls
     */
    protected function updateLocalizedUrls(ContentNode $contentNode, Collection $slugUrls)
    {
        $toRemove = [];
        foreach ($contentNode->getLocalizedUrls() as $localizedUrl) {
            $localeId = (int)$this->getLocaleId($localizedUrl->getLocalization());
            if ($slugUrls->containsKey($localeId)) {
                /** @var SlugUrl $slugUrl */
                $slugUrl = $slugUrls->get($localeId);
                $localizedUrl->setText($slugUrl->getUrl());
            } else {
                $toRemove[] = $localizedUrl;
            }
        }
        foreach ($toRemove as $removedLocalizedUrl) {
            $contentNode->removeLocalizedUrl($removedLocalizedUrl);
        }

        foreach ($slugUrls as $slugUrl) {
            if (!$this->getExistingLocalizedUrl($slugUrl, $contentNode)) {
                $localizedUrl = new LocalizedFallbackValue();
                $localizedUrl->setText($slugUrl->getUrl());
                $localizedUrl->setLocalization($slugUrl->getLocalization());
                $contentNode->addLocalizedUrl($localizedUrl);
            }
        }
    }
}
