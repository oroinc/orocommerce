<?php

namespace Oro\Bundle\CMSBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * ContentWidget should have not empty layout when possible values are not empty.
 */
class NotEmptyContentWidgetLayout extends NotBlank
{
    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
