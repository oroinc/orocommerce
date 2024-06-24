<?php

namespace Oro\Bundle\WebCatalogBundle\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validate that content node has no restrictions.
 */
class NodeHasNoRestrictions extends Constraint
{
    public string $message = 'oro.webcatalog.contentnode.scope.not_empty.message';
}
