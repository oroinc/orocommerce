<?php

namespace Oro\Bundle\CMSBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if the default scopes are used.
 */
class TextContentVariantScope extends Constraint
{
    public string $message = 'oro.cms.contentblock.content_variants.scopes.empty.message';
}
