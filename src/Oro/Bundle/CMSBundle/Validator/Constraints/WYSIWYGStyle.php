<?php

namespace Oro\Bundle\CMSBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint can be used to check if WYSIWYG style field is valid.
 */
class WYSIWYGStyle extends Constraint
{
    public string $message = 'oro.cms.wysiwyg.not_permitted_style.message';

    /**
     * {@inheritdoc}
     */
    public function validatedBy(): string
    {
        return WYSIWYGValidator::class;
    }
}
