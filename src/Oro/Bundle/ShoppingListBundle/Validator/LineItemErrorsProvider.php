<?php

namespace Oro\Bundle\ShoppingListBundle\Validator;

use Symfony\Component\Validator\Validator\ValidatorInterface;

use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Validator\Constraints\LineItemCollection as LineItemCollectionConstraint;

class LineItemErrorsProvider
{
    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @param ValidatorInterface $validator
     */
    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param LineItem[] $lineItems
     * @return array
     */
    public function getLineItemErrors($lineItems)
    {
        $lineItemErrors = $this->validator->validate($lineItems, [new LineItemCollectionConstraint]);
        $indexedLineItemErrors = [];
        foreach ($lineItemErrors as $error) {
            $indexedLineItemErrors[$error->getPropertyPath()][] = $error;
        }

        return $indexedLineItemErrors;
    }

    /**
     * @param LineItem[] $lineItems
     * @return bool
     */
    public function isLineItemListValid($lineItems)
    {
        return count($this->validator->validate($lineItems, [new LineItemCollectionConstraint()])) == 0;
    }
}
