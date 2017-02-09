<?php

namespace Oro\Bundle\RedirectBundle\Generator;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\Localization;
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
     * @param RoutingInformationProviderInterface $routingInformationProvider
     * @param UniqueSlugResolver $slugResolver
     * @param RedirectGenerator $redirectGenerator
     */
    public function __construct(
        RoutingInformationProviderInterface $routingInformationProvider,
        UniqueSlugResolver $slugResolver,
        RedirectGenerator $redirectGenerator
    ) {
        $this->routingInformationProvider = $routingInformationProvider;
        $this->slugResolver = $slugResolver;
        $this->redirectGenerator = $redirectGenerator;
    }

    /**
     * @param SluggableInterface $entity
     * @param bool $generateRedirects
     */
    public function generate(SluggableInterface $entity, $generateRedirects = false)
    {
        $slugUrls = $this->getResolvedSlugUrls($entity);

        $toRemove = [];

        foreach ($entity->getSlugs() as $slug) {
            $localizationId = $this->getLocalizationId($slug->getLocalization());

            if ($slugUrls->containsKey($localizationId)) {
                $slugUrl = $slugUrls->get($localizationId);

                $previousSlugUrl = $slug->getUrl();
                $updatedUrl = $slugUrl->getUrl();
                $slug->setUrl($updatedUrl);

                if ($generateRedirects) {
                    $this->redirectGenerator->generate($previousSlugUrl, $slug);
                }
            } else {
                $toRemove[] = $slug;
            }
        }

        foreach ($toRemove as $slugToRemove) {
            $entity->removeSlug($slugToRemove);
        }

        foreach ($slugUrls as $slugUrl) {
            if ($this->getExistingSlugs($slugUrl, $entity->getSlugs())->isEmpty()) {
                $routeData = $this->routingInformationProvider->getRouteData($entity);
                $entity->addSlug($this->createSlug($routeData, $slugUrl));
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
            $url = trim($slugPrototype->getString());
            if (!$slugPrototype->getFallback() && null !== $url && '' !== $url) {
                $filledSlugPrototypes[] = new SlugUrl(
                    $slugPrototype->getString(),
                    $slugPrototype->getLocalization()
                );
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
            $slugUrl = new SlugUrl($url, $localization);
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
