<?php

namespace Oro\Bundle\WebCatalogBundle\Generator;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\WebCatalogBundle\ContentVariantType\ContentVariantTypeRegistry;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

class SlugGenerator
{
    /**
     * @var ContentVariantTypeRegistry
     */
    protected $contentVariantTypeRegistry;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ContentVariantTypeRegistry $contentVariantTypeRegistry
     * @param ManagerRegistry $registry
     */
    public function __construct(ContentVariantTypeRegistry $contentVariantTypeRegistry, ManagerRegistry $registry)
    {
        $this->contentVariantTypeRegistry = $contentVariantTypeRegistry;
        $this->registry = $registry;
    }

    /**
     * @param ContentNode $contentNode
     */
    public function generate(ContentNode $contentNode)
    {
        $slugUrls = $this->prepareSlugUrls($contentNode);

        foreach ($slugUrls as $slugUrl) {
            $this->createSlugs($contentNode, $slugUrl);
        }
    }

    /**
     * @param ContentNode $contentNode
     * @param string $slugUrl
     */
    protected function createSlugs(ContentNode $contentNode, $slugUrl)
    {
        $em = $this->registry->getManagerForClass(Slug::class);

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

            $em->persist($slug);
        }

        $em->flush();
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
            $slugPrototype = $changedSlugPrototypeValue['slugPrototypeValue'];
            $locale = $changedSlugPrototypeValue['localization'];
            
            if (empty($parentNodeSlugUrls)) {
                $slugUrls[] = $slugPrototype;
            } elseif (array_key_exists($localeId, $parentNodeSlugUrls)) {
                $slugUrls[] = $parentNodeSlugUrls[$localeId] . '/' . $slugPrototype;
            } elseif ($fallbackSlug = $this->findFallbackSlug($locale, $parentNodeSlugUrls)) {
                $slugUrls[] = $fallbackSlug . '/' . $slugPrototype;
            }
        }

        return $slugUrls;
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
            foreach ($parentNode->getSlugs() as $parentNodeSlug) {
                $parentNodeSlugUrls[$parentNodeSlug->getLocalization()->getId()] = $parentNodeSlug->getUrl();
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
                    'slugPrototypeValue' => $slugPrototype->getString(),
                    'localization' => $slugPrototype->getLocalization()
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
        }

        return $localeHierarchy;
    }
}
