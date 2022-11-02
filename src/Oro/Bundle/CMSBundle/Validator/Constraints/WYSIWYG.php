<?php

namespace Oro\Bundle\CMSBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint can be used to check if WYSIWYG field is valid.
 */
class WYSIWYG extends Constraint
{
    /** @var string */
    public $message = 'oro.cms.wysiwyg.not_permitted_content.message';
}
