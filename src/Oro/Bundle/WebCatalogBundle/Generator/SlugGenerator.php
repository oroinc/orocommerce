<?php

namespace Oro\Bundle\WebCatalogBundle\Generator;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\WebCatalogBundle\ContentVariantType\ContentVariantTypeRegistry;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;

class SlugGenerator
{
    const ROOT_SLUG = '/';
    const SLUG_PROTOTYPE_VALUE = 'slugPrototypeValue';
    const SLUG_URL = 'slugUrl';
    const LOCALIZATION = 'localization';

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
        if (!$contentNode->getParentNode()) {
            // Slug url for root content node
            $slugUrls = [
                [
                    self::SLUG_URL => self::ROOT_SLUG,
                    self::LOCALIZATION => null
                ]
            ];
        } else {
            $slugUrls = $this->prepareSlugUrls($contentNode);
        }

        // Clear all already existing content variant slugs before generating new
        $this->clearNodeVariantSlugs($contentNode);
        
        foreach ($slugUrls as $slugUrl) {
            $this->createSlugs($contentNode, $slugUrl[self::SLUG_URL], $slugUrl[self::LOCALIZATION]);
        }
    }

    /**
     * @param ContentNode $contentNode
     */
    protected function clearNodeVariantSlugs(ContentNode $contentNode)
    {
        $contentVariants = $contentNode->getContentVariants();
        
        foreach ($contentVariants as $contentVariant) {
            $contentVariant->resetSlugs();
        }
    }
    
    /**
     * @param ContentNode $contentNode
     * @param string $slugUrl
     * @param Localization|null $localization
     */
    protected function createSlugs(ContentNode $contentNode, $slugUrl, Localization $localization = null)
    {
        $contentVariants = $contentNode->getContentVariants();

        foreach ($contentVariants as $contentVariant) {
            $contentVariantType = $this->contentVariantTypeRegistry->getContentVariantType($contentVariant->getType());
            $routeData = $contentVariantType->getRouteData($contentVariant);
            $scopes = $contentVariant->getScopes();

            $slug = new Slug();
            $slug->setUrl($slugUrl);

            foreach ($scopes as $scope) {
                $slug->addScope($scope);
            }

            $slug->setRouteName($routeData->getRoute());
            $slug->setRouteParameters($routeData->getRouteParameters());
            $slug->setLocalization($localization);

            $contentVariant->addSlug($slug);
        }
    }

    /**
     * @param ContentNode $contentNode
     * @return array
     */
    protected function prepareSlugUrls(ContentNode $contentNode)
    {
        $slugPrototypes = $contentNode->getSlugPrototypes();
        $changedSlugPrototypes = $this->getChangedSlugPrototypes($slugPrototypes);

        $parentNodeSlugUrls = $this->getParentNodeSlugUrls($contentNode);

        $slugUrls = [];
        foreach ($changedSlugPrototypes as $localeId => $changedSlugPrototypeValue) {
            $slugPrototype = $changedSlugPrototypeValue[self::SLUG_PROTOTYPE_VALUE];
            $locale = $changedSlugPrototypeValue[self::LOCALIZATION];
            
            if (empty($parentNodeSlugUrls)) {
                $slugUrls[] = [
                    self::SLUG_URL => Slug::DELIMITER . $slugPrototype,
                    self::LOCALIZATION => $locale
                ];
            } elseif (array_key_exists($localeId, $parentNodeSlugUrls)) {
                $slugUrls[] = [
                    self::SLUG_URL => $this->getUrl($parentNodeSlugUrls[$localeId], $slugPrototype),
                    self::LOCALIZATION => $locale
                ];
            } elseif ($fallbackSlug = $this->findFallbackSlug($locale, $parentNodeSlugUrls)) {
                $slugUrls[] = [
                    self::SLUG_URL => $this->getUrl($fallbackSlug, $slugPrototype),
                    self::LOCALIZATION => $locale
                ];
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
        return $parentUrl == self::ROOT_SLUG
            ? $parentUrl . $slugPrototype
            : $parentUrl . Slug::DELIMITER . $slugPrototype;
    }

    /**
     * @param ContentNode $contentNode
     * @return array
     */
    protected function getParentNodeSlugUrls(ContentNode $contentNode)
    {
        $parentNode = $contentNode->getParentNode();
        /** @var ContentVariant $contentVariant */
        $contentVariant = $parentNode->getContentVariants()->first();

        $parentNodeSlugUrls = [];
        if ($parentNode) {
            foreach ($contentVariant->getSlugs() as $parentNodeSlug) {
                $localeId = $parentNodeSlug->getLocalization() ? $parentNodeSlug->getLocalization()->getId() : null;
                $parentNodeSlugUrls[$localeId] = $parentNodeSlug->getUrl();
            }
        }

        return $parentNodeSlugUrls;
    }

    /**
     * @param Collection|LocalizedFallbackValue[] $slugPrototypes
     * @return array
     */
    protected function getChangedSlugPrototypes(Collection $slugPrototypes)
    {
        $changedSlugPrototypes = [];
        foreach ($slugPrototypes as $slugPrototype) {
            if (!$slugPrototype->getFallback()) {
                $localeId = $slugPrototype->getLocalization() ? $slugPrototype->getLocalization()->getId() : null;
                $changedSlugPrototypes[$localeId] = [
                    self::SLUG_PROTOTYPE_VALUE => $slugPrototype->getString(),
                    self::LOCALIZATION => $slugPrototype->getLocalization()
                ];
            }
        }

        return $changedSlugPrototypes;
    }

    /**
     * @param $localization
     * @param array $parentNodeSlugUrls
     * @return bool|string
     */
    protected function findFallbackSlug($localization, array $parentNodeSlugUrls)
    {
        $localeHierarchy = $this->getLocaleHierarchy($localization);

        foreach ($localeHierarchy as $localeId) {
            if (array_key_exists($localeId, $parentNodeSlugUrls)) {
                return $parentNodeSlugUrls[$localeId];
            }
        }

        return false;
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
}
