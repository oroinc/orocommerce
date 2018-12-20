<?php

namespace Oro\Bundle\WebCatalogBundle\Generator;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Generator\DTO\SlugUrl;
use Oro\Bundle\RedirectBundle\Generator\RedirectGenerator;
use Oro\Bundle\RedirectBundle\Generator\SlugUrlDiffer;
use Oro\Bundle\WebCatalogBundle\ContentVariantType\ContentVariantTypeRegistry;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Component\Routing\RouteData;

/**
 * Generates slugs and slug redirects for given content node.
 */
class SlugGenerator
{
    const ROOT_URL = '/';

    /**
     * @var ContentVariantTypeRegistry
     */
    protected $contentVariantTypeRegistry;

    /**
     * @var LocalizationHelper
     */
    private $localizationHelper;

    /**
     * @var RedirectGenerator
     */
    protected $redirectGenerator;

    /**
     * @var SlugUrlDiffer
     */
    protected $slugUrlDiffer;

    /**
     * @param ContentVariantTypeRegistry $contentVariantTypeRegistry
     * @param RedirectGenerator $redirectGenerator
     * @param LocalizationHelper $localizationHelper
     * @param SlugUrlDiffer $slugUrlDiffer
     */
    public function __construct(
        ContentVariantTypeRegistry $contentVariantTypeRegistry,
        RedirectGenerator $redirectGenerator,
        LocalizationHelper $localizationHelper,
        SlugUrlDiffer $slugUrlDiffer
    ) {
        $this->contentVariantTypeRegistry = $contentVariantTypeRegistry;
        $this->redirectGenerator = $redirectGenerator;
        $this->localizationHelper = $localizationHelper;
        $this->slugUrlDiffer = $slugUrlDiffer;
    }

    /**
     * @param ContentNode $contentNode
     * @param bool $generateRedirects
     */
    public function generate(ContentNode $contentNode, $generateRedirects = false)
    {
        if ($contentNode->getParentNode()) {
            $slugUrls = $this->prepareSlugUrls($contentNode);
        } else {
            // Slug url for root content node
            $slugUrls = new ArrayCollection([new SlugUrl(self::ROOT_URL)]);
        }

        $this->updateLocalizedUrls($contentNode, $slugUrls);
        $this->bindSlugs($contentNode, $slugUrls, $generateRedirects);

        if (!$contentNode->getChildNodes()->isEmpty()) {
            foreach ($contentNode->getChildNodes() as $childNode) {
                $this->generate($childNode, $generateRedirects);
            }
        }
    }

    /**
     * @param ContentNode $contentNode
     * @param SlugUrl[]|Collection $slugUrls
     * @param bool $generateRedirects
     */
    protected function bindSlugs(ContentNode $contentNode, Collection $slugUrls, $generateRedirects = false)
    {
        foreach ($contentNode->getContentVariants() as $contentVariant) {
            $contentVariantType = $this->contentVariantTypeRegistry->getContentVariantType($contentVariant->getType());
            $routeData = $contentVariantType->getRouteData($contentVariant);
            $scopes = $contentVariant->getScopes();

            $toRemove = [];
            // Remove slugs if content node scopes list is empty (no restrictions)
            if ($scopes->isEmpty()) {
                $contentVariant->resetSlugs();
                continue;
            }

            foreach ($contentVariant->getSlugs() as $slug) {
                $localeId = (int)$this->getLocaleId($slug->getLocalization());
                if ($slugUrls->containsKey($localeId)) {
                    $previousSlug = clone $slug;

                    /** @var SlugUrl $slugUrl */
                    $slugUrl = $slugUrls->get($localeId);
                    $slug->resetScopes();
                    $this->fillSlug($slug, $slugUrl, $routeData, $scopes);

                    $this->redirectGenerator->updateRedirects($previousSlug->getUrl(), $slug);

                    if ($generateRedirects) {
                        $this->redirectGenerator->generateForSlug($previousSlug, $slug);
                    }
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
     * @return ArrayCollection
     */
    public function getSlugUrls(ContentNode $contentNode)
    {
        $parentUrls = $this->getParentNodeSlugUrls($contentNode);

        $slugUrls = new ArrayCollection();
        $slugPrototypes = $contentNode->getSlugPrototypes();
        if (!$slugPrototypes->count()) {
            return $slugUrls;
        }

        $localizations = array_merge([null], $this->localizationHelper->getLocalizations());
        foreach ($localizations as $localization) {
            $parentUrl = $this->localizationHelper->getLocalizedValue($parentUrls, $localization);

            $url = $this->getUrl(
                $parentUrl->getText(),
                $this->localizationHelper->getLocalizedValue($slugPrototypes, $localization)->getString()
            );

            $slugUrls->set($this->getLocaleId($localization), new SlugUrl($url, $localization));
        }

        return $slugUrls;
    }

    /**
     * @param ContentNode $contentNode
     *
     * @return Collection|SlugUrl[]
     */
    public function prepareSlugUrls(ContentNode $contentNode)
    {
        $filledSlugPrototypes = $this->getFilledSlugPrototypes($contentNode);
        $parentNodeSlugUrls = $this->getParentNodeSlugUrls($contentNode);

        $slugUrls = new ArrayCollection();
        foreach ($filledSlugPrototypes as $changedSlugPrototypeValue) {
            $slugPrototype = $changedSlugPrototypeValue->getUrl();
            $locale = $changedSlugPrototypeValue->getLocalization();

            $parentSlugUrl = $this->localizationHelper->getLocalizedValue($parentNodeSlugUrls, $locale);

            if ($parentSlugUrl) {
                $url = $this->getUrl($parentSlugUrl->getText(), $slugPrototype);
                $slugUrls->set((int)$this->getLocaleId($locale), new SlugUrl($url, $locale, $slugPrototype));
            }
        }

        return $slugUrls;
    }

    /**
     * Get slugs URL for moved node
     *
     * @param ContentNode $targetContentNode
     * @param ContentNode $sourceContentNode
     *
     * @return array
     */
    public function getSlugsUrlForMovedNode($targetContentNode, $sourceContentNode)
    {
        $urlsBeforeMove = $this->prepareSlugUrls($sourceContentNode);

        $sourceContentNode->setParentNode($targetContentNode);
        $urlsAfterMove = $this->prepareSlugUrls($sourceContentNode);

        return $this->slugUrlDiffer->getSlugUrlsChanges($urlsBeforeMove, $urlsAfterMove);
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
     * @return Collection|SlugUrl[]
     */
    protected function getParentNodeSlugUrls(ContentNode $contentNode)
    {
        $parentNode = $contentNode->getParentNode();
        if (!$parentNode) {
            return new ArrayCollection();
        }

        return $parentNode->getLocalizedUrls();
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
            // empty() function can not be used here, as '0' is a valid slug prototype value
            if ($value !== '' && $value !== null && !$slugPrototype->getFallback()) {
                $localeId = $this->getLocaleId($slugPrototype->getLocalization());

                $changedSlugPrototypes[$localeId] = new SlugUrl($value, $slugPrototype->getLocalization());
            }
        }

        return $changedSlugPrototypes;
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

        return 0;
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
        $slug->setSlugPrototype($slugUrl->getSlug());
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
