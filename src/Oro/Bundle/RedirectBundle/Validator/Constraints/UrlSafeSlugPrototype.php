<?php

namespace Oro\Bundle\RedirectBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint on slug prototype to be url safe.
 *
 * @Annotation
 */
class UrlSafeSlugPrototype extends Constraint
{
    public bool $allowSlashes = false;
}
