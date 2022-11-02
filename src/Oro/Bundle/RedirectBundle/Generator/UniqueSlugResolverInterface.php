<?php

namespace Oro\Bundle\RedirectBundle\Generator;

use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Generator\DTO\SlugUrl;

/**
 * The interface for classes that need to resolve entity slug uniqueness.
 */
interface UniqueSlugResolverInterface
{
    public function resolve(SlugUrl $slugUrl, SluggableInterface $entity): string;
}
