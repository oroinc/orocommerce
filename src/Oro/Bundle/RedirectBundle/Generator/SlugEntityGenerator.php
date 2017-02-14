<?php

namespace Oro\Bundle\RedirectBundle\Generator;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\RedirectBundle\Cache\UrlStorageCache;
use Oro\Bundle\RedirectBundle\Entity\LocalizedSlugPrototypeAwareInterface;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Generator\DTO\SlugUrl;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProviderInterface;
use Oro\Component\Routing\RouteData;

class SlugEntityGenerator
{
    /**
     * @var RoutingInformationProviderInterface
     */
    protected $routingInformationProvider;

    /**
     * @var UniqueSlugResolver
     */
    protected $slugResolver;

    /**
     * @var UrlStorageCache
     */
    private $urlStorageCache;

    /**
     * @param RoutingInformationProviderInterface $routingInformationProvider
     * @param UniqueSlugResolver $slugResolver
     * @param RedirectGenerator $redirectGenerator
     * @param UrlStorageCache $urlStorageCache
     */
    public function __construct(
        RoutingInformationProviderInterface $routingInformationProvider,
        UniqueSlugResolver $slugResolver,
        RedirectGenerator $redirectGenerator,
        UrlStorageCache $urlStorageCache
    ) {
        $this->routingInformationProvider = $routingInformationProvider;
        $this->slugResolver = $slugResolver;
        $this->redirectGenerator = $redirectGenerator;
        $this->urlStorageCache = $urlStorageCache;
    }

    /**
     * @param SluggableInterface $entity
     * @param bool $generateRedirects
     */
    public function generate(SluggableInterface $entity, $generateRedirects = false)
    {
        $slugUrls = $this->getResolvedSlugUrls($entity);

        /** @var Slug[] $toRemove */
        $toRemove = [];
        foreach ($entity->getSlugs() as $slug) {
            $localizationId = $this->getLocalizationId($slug->getLocalization());

            // Update existing
            if ($slugUrls->containsKey($localizationId)) {
                $slugUrl = $slugUrls->get($localizationId);

                $previousSlugUrl = $slug->getUrl();
                $updatedUrl = $slugUrl->getUrl();
                $slug->setUrl($updatedUrl);
                $slug->setSlugPrototype($slugUrl->getSlug());

                if ($generateRedirects) {
                    $this->redirectGenerator->generate($previousSlugUrl, $slug);
                }

                $this->urlStorageCache->setUrl(
                    $slug->getRouteName(),
                    $slug->getRouteParameters(),
                    $slug->getUrl(),
                    $slug->getSlugPrototype()
                );
            } else {
                $toRemove[] = $slug;
            }
        }

        // Remove deleted
        foreach ($toRemove as $slugToRemove) {
            $entity->removeSlug($slugToRemove);

            $this->urlStorageCache->removeUrl(
                $slugToRemove->getRouteName(),
                $slugToRemove->getRouteParameters()
            );
        }

        // Add new
        foreach ($slugUrls as $slugUrl) {
            if ($this->getExistingSlugs($slugUrl, $entity->getSlugs())->isEmpty()) {
                $routeData = $this->routingInformationProvider->getRouteData($entity);
                $slug = $this->createSlug($routeData, $slugUrl);
                $entity->addSlug($slug);

                $this->urlStorageCache->setUrl(
                    $slug->getRouteName(),
                    $slug->getRouteParameters(),
                    $slug->getUrl(),
                    $slug->getSlugPrototype()
                );
            }
        }
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
    protected function getSlugUrls(SluggableInterface $entity)
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
     * @return Collection|null|Slug[]
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
     * @param string SluggableInterface $entity
     * @param string $slugPrototype
     * @return string
     */
    protected function getUrl(SluggableInterface $entity, $slugPrototype)
    {
        $prefix = trim($this->routingInformationProvider->getUrlPrefix($entity), Slug::DELIMITER);

        $url = Slug::DELIMITER  . $slugPrototype;
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
        $slugUrls = $this->getSlugUrls($entity);

        foreach ($slugUrls as $slugUrl) {
            $slugUrl->setUrl($this->slugResolver->resolve($slugUrl, $entity));
        }

        return $slugUrls;
    }

    /**
     * @param Localization|null $localization
     * @return int
     */
    public function getLocalizationId(Localization $localization = null)
    {
        if ($localization) {
            return $localization->getId();
        }

        return 0;
    }
}
