<?php

namespace Oro\Bundle\RedirectBundle\Generator;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\RedirectBundle\Cache\UrlCacheInterface;
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
     * @var UrlCacheInterface
     */
    private $urlCache;

    /**
     * @var RedirectGenerator
     */
    private $redirectGenerator;

    /**
     * @param RoutingInformationProviderInterface $routingInformationProvider
     * @param UniqueSlugResolver $slugResolver
     * @param RedirectGenerator $redirectGenerator
     * @param UrlCacheInterface $urlCache
     */
    public function __construct(
        RoutingInformationProviderInterface $routingInformationProvider,
        UniqueSlugResolver $slugResolver,
        RedirectGenerator $redirectGenerator,
        UrlCacheInterface $urlCache
    ) {
        $this->routingInformationProvider = $routingInformationProvider;
        $this->slugResolver = $slugResolver;
        $this->redirectGenerator = $redirectGenerator;
        $this->urlCache = $urlCache;
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

                $this->redirectGenerator->updateRedirects($previousSlugUrl, $slug);

                if ($generateRedirects) {
                    $this->redirectGenerator->generate($previousSlugUrl, $slug);
                }

                $this->urlCache->setUrl(
                    $slug->getRouteName(),
                    $slug->getRouteParameters(),
                    $slug->getUrl(),
                    $slug->getSlugPrototype(),
                    $this->getLocalizationId($slug->getLocalization())
                );
            } else {
                $toRemove[] = $slug;
            }
        }

        // Remove deleted
        foreach ($toRemove as $slugToRemove) {
            $entity->removeSlug($slugToRemove);

            $this->urlCache->removeUrl(
                $slugToRemove->getRouteName(),
                $slugToRemove->getRouteParameters(),
                $this->getLocalizationId($slugToRemove->getLocalization())
            );
        }

        // Add new
        foreach ($slugUrls as $slugUrl) {
            if ($this->getExistingSlugs($slugUrl, $entity->getSlugs())->isEmpty()) {
                $routeData = $this->routingInformationProvider->getRouteData($entity);
                $slug = $this->createSlug($routeData, $slugUrl);
                $entity->addSlug($slug);

                $this->urlCache->setUrl(
                    $slug->getRouteName(),
                    $slug->getRouteParameters(),
                    $slug->getUrl(),
                    $slug->getSlugPrototype(),
                    $this->getLocalizationId($slug->getLocalization())
                );
            }
        }

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

        foreach ($slugUrls as $slugUrl) {
            $url = $this->slugResolver->resolve($slugUrl, $entity);

            $slugPrototype = substr($url, strrpos($url, Slug::DELIMITER) + 1);

            $slugUrl->setUrl($url);
            $slugUrl->setSlug($slugPrototype);
        }

        return $slugUrls;
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
}
