<?php

namespace Oro\Bundle\WebCatalogBundle\Generator;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\WebCatalogBundle\ContentVariantType\ContentVariantTypeRegistry;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Generator\DTO\SlugUrl;

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
            $slugUrls = [new SlugUrl(self::ROOT_URL)];
        }

        foreach ($slugUrls as $slugUrl) {
            $this->bindSlugs($contentNode, $slugUrl);
        }
    }

    /**
     * @param ContentNode $contentNode
     * @param SlugUrl $slugUrl
     */
    protected function bindSlugs(ContentNode $contentNode, SlugUrl $slugUrl)
    {
        $contentVariants = $contentNode->getContentVariants();

        foreach ($contentVariants as $contentVariant) {
            $contentVariantType = $this->contentVariantTypeRegistry->getContentVariantType($contentVariant->getType());
            $routeData = $contentVariantType->getRouteData($contentVariant);
            $scopes = $contentVariant->getScopes();

            $slug = $this->getExistingSlug($slugUrl, $contentVariant);
            if ($slug) {
                $slug->resetScopes();
            } else {
                $slug = new Slug();
                $slug->setUrl($slugUrl->getUrl());
                $slug->setLocalization($slugUrl->getLocalization());

                $contentVariant->addSlug($slug);
            }

            foreach ($scopes as $scope) {
                $slug->addScope($scope);
            }

            $slug->setRouteName($routeData->getRoute());
            $slug->setRouteParameters($routeData->getRouteParameters());
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
     * @return SlugUrl[]
     */
    protected function prepareSlugUrls(ContentNode $contentNode)
    {
        $filledSlugPrototypes = $this->getFilledSlugPrototypes($contentNode);
        $parentNodeSlugUrls = $this->getParentNodeSlugUrls($contentNode);

        $slugUrls = [];
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
                $slugUrls[] = new SlugUrl($url, $locale);
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
     * @return array
     */
    protected function getParentNodeSlugUrls(ContentNode $contentNode)
    {
        $parentNode = $contentNode->getParentNode();

        $parentNodeSlugUrls = [];
        if ($parentNode) {
            // We can use any parent content variant.
            // Content variant slugs are generated based on the Node data
            // and are identical for all node content variants.
            /** @var ContentVariant $contentVariant */
            $contentVariant = $parentNode->getContentVariants()->first();

            if ($contentVariant) {
                foreach ($contentVariant->getSlugs() as $parentNodeSlug) {
                    $localeId = $this->getLocaleId($parentNodeSlug->getLocalization());
                    $parentNodeSlugUrls[$localeId] = $parentNodeSlug->getUrl();
                }
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
            if (!$slugPrototype->getFallback()) {
                $localeId = $this->getLocaleId($slugPrototype->getLocalization());

                $changedSlugPrototypes[$localeId] = new SlugUrl(
                    $slugPrototype->getString(),
                    $slugPrototype->getLocalization()
                );
            }
        }

        return $changedSlugPrototypes;
    }

    /**
     * @param Localization $localization
     * @param array $parentNodeSlugUrls
     * @return string|null
     */
    protected function findFallbackSlug(Localization $localization, array $parentNodeSlugUrls)
    {
        $localeHierarchy = $this->getLocaleHierarchy($localization);

        foreach ($localeHierarchy as $localeId) {
            if (array_key_exists($localeId, $parentNodeSlugUrls)) {
                return $parentNodeSlugUrls[$localeId];
            }
        }

        return null;
    }

    /**
     * @param Localization $localization
     * @return array
     */
    protected function getLocaleHierarchy(Localization $localization)
    {
        $localeHierarchy = [];

        $parent = $localization->getParentLocalization();
        if ($parent) {
            $localeHierarchy[] = $parent->getId();
            $localeHierarchy = array_merge($localeHierarchy, $this->getLocaleHierarchy($parent));
        } else {
            // For default value without locale
            $localeHierarchy = [null];
        }

        return $localeHierarchy;
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
}
