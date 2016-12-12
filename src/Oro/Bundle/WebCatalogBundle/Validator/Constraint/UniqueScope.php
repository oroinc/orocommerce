<?php

namespace Oro\Bundle\WebCatalogBundle\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

class UniqueScope extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.webcatalog.scope.unique.message';
}
