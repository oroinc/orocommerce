<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\ImageSlide;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Sets existing image slides to use 'eager' loading to preserve their current behavior, as previously
 * the "loading" attribute was not set on image slides, and the existing template had hard-coded "loading: 'eager'".
 *
 * New slides have "loading" set to 'lazy' by default.
 * @see ImageSlide::$loading
 * @see \Oro\Bundle\CMSBundle\Migrations\Schema\v1_17\AddImageSlideLoadingOptions
 */
class SetExistingImageSlidesToEagerLoading extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        if ($this->container->get(ApplicationState::class)->isInstalled()) {
            $manager->getConnection()->executeStatement(
                'UPDATE oro_cms_image_slide SET loading = :eager WHERE loading = :lazy',
                [
                    'eager' => ImageSlide::LOADING_EAGER,
                    'lazy' => ImageSlide::LOADING_LAZY,
                ]
            );
        }
    }
}
