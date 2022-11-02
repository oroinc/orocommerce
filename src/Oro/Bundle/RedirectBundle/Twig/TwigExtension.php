<?php

namespace Oro\Bundle\RedirectBundle\Twig;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Generator\SlugEntityGenerator;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Provides a Twig filters:
 *   - get_slug_urls_for_prototypes - return collection of slugs generated based on slug prototypes of the entity
 */
class TwigExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('get_slug_urls_for_prototypes', [$this, 'getSlugsByEntitySlugPrototypes']),
        ];
    }

    /**
     * @param SluggableInterface $entity
     * @return Collection|Slug[]
     */
    public function getSlugsByEntitySlugPrototypes(SluggableInterface $entity)
    {
        return $this->getSlugEntityGenerator()->getSlugsByEntitySlugPrototypes($entity);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            SlugEntityGenerator::class
        ];
    }

    private function getSlugEntityGenerator(): SlugEntityGenerator
    {
        return $this->container->get(SlugEntityGenerator::class);
    }
}
