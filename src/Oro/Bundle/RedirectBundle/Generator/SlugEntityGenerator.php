<?php

namespace Oro\Bundle\RedirectBundle\Generator;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\RedirectBundle\Cache\Dumper\SluggableUrlDumper;
use Oro\Bundle\RedirectBundle\Entity\LocalizedSlugPrototypeAwareInterface;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Generator\DTO\SlugUrl;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProviderInterface;
use Oro\Component\Routing\RouteData;

/**
 * Removes old caches
 * Generates new caches (only "slug" that were explicitly updated)
 */
class SlugEntityGenerator
{
    private RoutingInformationProviderInterface $routingInformationProvider;
    private UniqueSlugResolverInterface $slugResolver;
    private RedirectGenerator $redirectGenerator;
    private SluggableUrlDumper $urlCacheDumper;

    public function __construct(
        RoutingInformationProviderInterface $routingInformationProvider,
        UniqueSlugResolverInterface $slugResolver,
        RedirectGenerator $redirectGenerator,
        SluggableUrlDumper $urlCacheDumper
    ) {
        $this->routingInformationProvider = $routingInformationProvider;
        $this->slugResolver = $slugResolver;
        $this->redirectGenerator = $redirectGenerator;
        $this->urlCacheDumper = $urlCacheDumper;
    }

    /**
     * @param SluggableInterface $entity
     * @param bool $generateRedirects
     */
    public function generate(SluggableInterface $entity, $generateRedirects = false)
    {
        $this->generateWithoutCacheDump($entity, $generateRedirects);

        $this->urlCacheDumper->dump($entity);
    }

    /**
     * @param SluggableInterface $entity
     * @param bool $generateRedirects
     */
    public function generateWithoutCacheDump(SluggableInterface $entity, $generateRedirects = false)
    {
        $slugUrls = $this->getResolvedSlugUrls($entity);

        /** @var Slug[] $toRemove */
        $toRemove = [];
        foreach ($entity->getSlugs() as $slug) {
            $localizationId = $this->getLocalizationId($slug->getLocalization());

            if ($slugUrls->containsKey($localizationId)) {
                // Update existing
                $this->updateExistingSlug($slugUrls, $localizationId, $slug, $generateRedirects);
            } else {
                $toRemove[] = $slug;
            }
        }

        // Remove deleted
        foreach ($toRemove as $slugToRemove) {
            $entity->removeSlug($slugToRemove);
        }

        // Add new
        $this->addNewSlugs($entity, $slugUrls);
        $this->updateSlugPrototypes($entity, $slugUrls);
    }

    /**
     * @param LocalizedSlugPrototypeAwareInterface $entity
     * @return array|SlugUrl[]
     */
    protected function getFilledSlugPrototypes(LocalizedSlugPrototypeAwareInterface $entity)
    {
        $slugPrototypes = $entity->getSlugPrototypes();
        $filledSlugPrototypes = [];
        foreach ($slugPrototypes as $slugPrototype) {
            $value = trim($slugPrototype->getString());
            // empty() function can not be used here, as '0' is a valid slug prototype value
            if ($value !== '' && $value !== null && !$slugPrototype->getFallback()) {
                $filledSlugPrototypes[] = new SlugUrl($value, $slugPrototype->getLocalization());
            }
        }

        return $filledSlugPrototypes;
    }

    /**
     * @param SluggableInterface $entity
     * @return Collection|SlugUrl[]
     */
    public function prepareSlugUrls(SluggableInterface $entity)
    {
        $filledSlugPrototypes = $this->getFilledSlugPrototypes($entity);
        $slugUrls = new ArrayCollection();
        foreach ($filledSlugPrototypes as $filledSlugPrototype) {
            $slugPrototype = $filledSlugPrototype->getUrl();
            $localization = $filledSlugPrototype->getLocalization();

            $url = $this->getUrl($entity, $slugPrototype);
            $slugUrl = new SlugUrl($url, $localization, $slugPrototype);
            $slugUrls->set($this->getLocalizationId($localization), $slugUrl);
        }

        return $slugUrls;
    }

    /**
     * @param SlugUrl $slugUrl
     * @param Collection $slugs
     * @return Collection|Slug[]
     */
    protected function getExistingSlugs(SlugUrl $slugUrl, Collection $slugs)
    {
        return $slugs->filter(
            function (Slug $slug) use ($slugUrl) {
                return $slugUrl->getUrl() === $slug->getUrl()
                    && $slugUrl->getLocalization() === $slug->getLocalization();
            }
        );
    }

    /**
     * @param SluggableInterface $entity
     * @param string $slugPrototype
     * @return string
     */
    protected function getUrl(SluggableInterface $entity, $slugPrototype)
    {
        $prefix = trim($this->routingInformationProvider->getUrlPrefix($entity), Slug::DELIMITER);

        $url = Slug::DELIMITER . $slugPrototype;
        if ($prefix) {
            $url = Slug::DELIMITER . $prefix . $url;
        }

        return $url;
    }

    /**
     * @param RouteData $routeData
     * @param SlugUrl $slugUrl
     * @return Slug
     */
    protected function createSlug(RouteData $routeData, SlugUrl $slugUrl)
    {
        $slug = new Slug();
        $slug->setUrl($slugUrl->getUrl());
        $slug->setSlugPrototype($slugUrl->getSlug());
        $slug->setLocalization($slugUrl->getLocalization());
        $slug->setRouteName($routeData->getRoute());
        $slug->setRouteParameters($routeData->getRouteParameters());

        return $slug;
    }

    /**
     * @param SluggableInterface $entity
     * @return Collection|SlugUrl[]
     */
    private function getResolvedSlugUrls(SluggableInterface $entity)
    {
        $slugUrls = $this->prepareSlugUrls($entity);
        $prefix = $this->getSlugPrefix($entity);

        foreach ($slugUrls as $slugUrl) {
            $url = $this->slugResolver->resolve($slugUrl, $entity);

            $slugUrl->setUrl($url);
            $slugUrl->setSlug(preg_replace($prefix, '$2', $url));
        }

        return $slugUrls;
    }

    /**
     * @param SluggableInterface $entity
     * @return Collection|SlugUrl[]
     */
    public function getSlugsByEntitySlugPrototypes(SluggableInterface $entity)
    {
        $slugUrls = $this->prepareSlugUrls($entity);
        $prefix = $this->getSlugPrefix($entity);

        $routeData = $this->routingInformationProvider->getRouteData($entity);
        $slugs = new ArrayCollection();
        foreach ($slugUrls as $slugUrl) {
            $slugUrl->setSlug(preg_replace($prefix, '$2', $slugUrl->getUrl()));

            $slugs->add($this->createSlug($routeData, $slugUrl));
        }

        return $slugs;
    }

    /**
     * @param SluggableInterface $entity
     * @param SlugUrl[] $slugUrls
     */
    private function updateSlugPrototypes(SluggableInterface $entity, $slugUrls)
    {
        $slugPrototypesByLocalizationId = [];
        foreach ($entity->getSlugPrototypes() as $slugPrototype) {
            $slugPrototypesByLocalizationId[$this->getLocalizationId($slugPrototype->getLocalization())] =
                $slugPrototype;
        }

        foreach ($slugUrls as $localizationId => $slugUrl) {
            if (isset($slugPrototypesByLocalizationId[$localizationId])) {
                $slugPrototypesByLocalizationId[$localizationId]->setString($slugUrl->getSlug());
            }
        }
    }

    /**
     * @param Localization|null $localization
     * @return int
     */
    private function getLocalizationId(Localization $localization = null)
    {
        if ($localization) {
            return $localization->getId();
        }

        return 0;
    }

    private function getSlugPrefix(SluggableInterface $entity): string
    {
        return sprintf(
            '~^\%s?(%s\%s)?(.+)~',
            Slug::DELIMITER,
            preg_quote(trim($this->routingInformationProvider->getUrlPrefix($entity), Slug::DELIMITER), '~'),
            Slug::DELIMITER
        );
    }

    private function addNewSlugs(SluggableInterface $entity, Collection $slugUrls): void
    {
        foreach ($slugUrls as $slugUrl) {
            if ($this->getExistingSlugs($slugUrl, $entity->getSlugs())->isEmpty()) {
                $routeData = $this->routingInformationProvider->getRouteData($entity);
                $slug = $this->createSlug($routeData, $slugUrl);
                if ($entity instanceof OrganizationAwareInterface) {
                    $slug->setOrganization($entity->getOrganization());
                }

                $entity->addSlug($slug);
            }
        }
    }

    private function updateExistingSlug(
        Collection $slugUrls,
        int $localizationId,
        Slug $slug,
        bool $generateRedirects
    ): void {
        $slugUrl = $slugUrls->get($localizationId);

        $previousSlug = clone $slug;
        $updatedUrl = $slugUrl->getUrl();
        $slug->setUrl($updatedUrl);
        $slug->setSlugPrototype($slugUrl->getSlug());

        $this->redirectGenerator->updateRedirects($previousSlug->getUrl(), $slug);

        if ($generateRedirects) {
            $this->redirectGenerator->generateForSlug($previousSlug, $slug);
        }
    }
}
