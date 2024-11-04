<?php

namespace Oro\Bundle\WebCatalogBundle\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validate uniqueness of slug prototypes created within same parent node.
 */
class UniqueSlugPrototype extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.webcatalog.contentnode.slug_prototype.unique.message';

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
