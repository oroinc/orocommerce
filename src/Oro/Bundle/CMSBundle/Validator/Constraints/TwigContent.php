<?php

namespace Oro\Bundle\CMSBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint can be used to check if WYSIWYG field contains valid twig content.
 */
class TwigContent extends Constraint
{
    /** @var string */
    public $message = 'oro.cms.wysiwyg.twig_content.message';
}
