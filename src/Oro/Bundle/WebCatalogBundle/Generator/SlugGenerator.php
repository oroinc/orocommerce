<?php

namespace Oro\Bundle\WebCatalogBundle\Generator;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Generator\DTO\SlugUrl;
use Oro\Bundle\RedirectBundle\Generator\RedirectGenerator;
use Oro\Bundle\RedirectBundle\Generator\SlugUrlDiffer;
use Oro\Bundle\RedirectBundle\Helper\SlugScopeHelper;
use Oro\Bundle\WebCatalogBundle\ContentVariantType\ContentVariantTypeRegistry;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Resolver\UniqueContentNodeSlugPrototypesResolver;
use Oro\Component\Routing\RouteData;

/**
 * Generates slugs and slug redirects for given content node.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class SlugGenerator
{
    public const ROOT_URL = '/';

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
     * @var UniqueContentNodeSlugPrototypesResolver
     */
    private $uniqueSlugPrototypesResolver;

    private DoctrineHelper $doctrineHelper;

    public function __construct(
        ContentVariantTypeRegistry $contentVariantTypeRegistry,
        RedirectGenerator $redirectGenerator,
        LocalizationHelper $localizationHelper,
        SlugUrlDiffer $slugUrlDiffer,
        UniqueContentNodeSlugPrototypesResolver $uniqueSlugPrototypesResolver,
        DoctrineHelper $doctrineHelper
    ) {
        $this->contentVariantTypeRegistry = $contentVariantTypeRegistry;
        $this->redirectGenerator = $redirectGenerator;
        $this->localizationHelper = $localizationHelper;
        $this->slugUrlDiffer = $slugUrlDiffer;
        $this->uniqueSlugPrototypesResolver = $uniqueSlugPrototypesResolver;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param ContentNode $contentNode
     * @param bool $generateRedirects
     */
    public function generate(ContentNode $contentNode, $generateRedirects = false)
    {
        $this->uniqueSlugPrototypesResolver
            ->resolveSlugPrototypeUniqueness($contentNode->getParentNode(), $contentNode);
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
        $organization = $contentNode->getWebCatalog()->getOrganization();
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
                    $slug = $this->getOrphanOrCreateNewSlug($organization->getId(), $slugUrl, $scopes);
                    $this->fillSlug($slug, $slugUrl, $routeData, $scopes);
                    $slug->setOrganization($organization);
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
            if (
                $slugUrl->getUrl() === $existingSlug->getUrl()
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
        $this->uniqueSlugPrototypesResolver
            ->resolveSlugPrototypeUniqueness($targetContentNode, $sourceContentNode);
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
    protected function getLocaleId(?Localization $localization = null)
    {
        if ($localization) {
            return $localization->getId();
        }

        return 0;
    }

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
            if (
                $slugUrl->getUrl() === $localizedUrl->getText()
                && $slugUrl->getLocalization() === $localizedUrl->getLocalization()
            ) {
                return $localizedUrl;
            }
        }

        return null;
    }

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

    /**
     * This method needed for avoid multiple problems with orphan slugs
     * that violates constraint oro_redirect_slug_deferrable_uidx,
     * if we have old orphan slug with organization_id, url_hash and scopes_hash
     * we take it, fill and reuse it, if not - creates new one
     *
     * @param int $organizationId
     * @param SlugUrl $slugUrl
     * @param Collection|null $scopes
     * @return Slug
     */
    private function getOrphanOrCreateNewSlug(int $organizationId, SlugUrl $slugUrl, ?Collection $scopes): Slug
    {
        $urlHash = md5($slugUrl->getUrl());
        $scopesHash = SlugScopeHelper::getScopesHash($scopes, $slugUrl->getLocalization());

        $slugRepository = $this->doctrineHelper->getEntityRepository(Slug::class);
        $slug = $slugRepository->getSlugByOrganizationAndHashes($organizationId, $urlHash, $scopesHash);

        if (!$slug) {
            $slug = new Slug();
        }

        return $slug;
    }
}
