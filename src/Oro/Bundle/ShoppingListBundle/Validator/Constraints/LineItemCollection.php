<?php

namespace Oro\Bundle\ShoppingListBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class LineItemCollection extends Constraint
{
    /**
     * @var mixed
     */
    protected $additionalContext;

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    /**
     * {@inheritDoc}
     */
    public function validatedBy()
    {
        return 'oro_shopping_list_line_item_collection_validator';
    }

    /**
     * @return mixed
     */
    public function getAdditionalContext()
    {
        return $this->additionalContext;
    }

    /**
     * @param mixed $additionalContext
     * @return $this
     */
    public function setAdditionalContext($additionalContext)
    {
        $this->additionalContext = $additionalContext;

        return $this;
    }
}
