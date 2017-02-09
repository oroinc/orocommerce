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
     * @param RoutingInformationProviderInterface $routingInformationProvider
     */
    public function __construct(RoutingInformationProviderInterface $routingInformationProvider)
    {
        $this->routingInformationProvider = $routingInformationProvider;
    }

    /**
     * @param SluggableInterface $entity
     */
    public function generate(SluggableInterface $entity)
    {
        $slugUrls = $this->getSlugUrls($entity);

        $toRemove = [];
        foreach ($entity->getSlugs() as $slug) {
            $localizationId = $this->getLocalizationId($slug->getLocalization());
            if ($slugUrls->containsKey($localizationId)) {
                /** @var SlugUrl $slugUrl */
                $slugUrl = $slugUrls->get($localizationId);
                $slug->setUrl($slugUrl->getUrl());
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
            $value = $slugPrototype->getString();
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
        return $this->routingInformationProvider->getUrlPrefix($entity) . Slug::DELIMITER . $slugPrototype;
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
