<?php

namespace Oro\Bundle\CMSBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint can be used to check if WYSIWYG field can be created.
 */
class WYSIWYGFieldsName extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.cms.wysiwyg.field_name_exist';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
