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

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
