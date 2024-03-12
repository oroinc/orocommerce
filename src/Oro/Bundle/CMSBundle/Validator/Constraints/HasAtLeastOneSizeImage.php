<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if image group contains at least one image for any size
 */
class HasAtLeastOneSizeImage extends Constraint
{
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
