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

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
