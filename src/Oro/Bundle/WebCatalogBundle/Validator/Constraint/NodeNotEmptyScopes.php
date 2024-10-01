<?php

namespace Oro\Bundle\WebCatalogBundle\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validate that content node has scopes assigned.
 */
class NodeNotEmptyScopes extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.webcatalog.contentnode.scope.empty.message';

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
