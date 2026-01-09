<?php

namespace Oro\Bundle\WebCatalogBundle\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint ensuring that content variant scopes are unique within a content node.
 *
 * This constraint prevents multiple non-default content variants from being assigned to the same scope
 * (combination of customer, customer group, and website). Each scope can only have one content variant,
 * ensuring unambiguous content resolution when determining which variant to display for a given context.
 */
class UniqueScope extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.webcatalog.scope.unique.message';
}
