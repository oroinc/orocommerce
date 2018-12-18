<?php

namespace Oro\Bundle\WebCatalogBundle\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validate that non-default content variants contains non-default scopes.
 */
class NotEmptyScopes extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.webcatalog.scope.empty.message';
}
