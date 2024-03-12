<?php

namespace Oro\Bundle\RedirectBundle\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * Constraint on slug prototype to be url safe.
 *
 * @Annotation
 */
#[Attribute]
class UrlSafeSlugPrototype extends Constraint
{
    public bool $allowSlashes = false;
}
