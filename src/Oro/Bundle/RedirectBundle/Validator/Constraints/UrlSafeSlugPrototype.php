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
    const ALIAS = 'oro_redirect_url_safe_slug_prototype_validator';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_redirect_url_safe_slug_prototype_validator';
    }
}
