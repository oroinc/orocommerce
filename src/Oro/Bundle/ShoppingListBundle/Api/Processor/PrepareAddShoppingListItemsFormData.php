<?php

namespace Oro\Bundle\ShoppingListBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresourceContext;
use Oro\Bundle\ApiBundle\Util\EntityInstantiator;
use Oro\Bundle\ShoppingListBundle\Api\Model\ShoppingListItemCollection;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Prepares the form data for "add to cart" sub-resource.
 */
class PrepareAddShoppingListItemsFormData implements ProcessorInterface
{
    private EntityInstantiator $entityInstantiator;
    private ValidatorInterface $validator;

    public function __construct(EntityInstantiator $entityInstantiator, ValidatorInterface $validator)
    {
        $this->entityInstantiator = $entityInstantiator;
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ChangeSubresourceContext $context */

        $associationName = $context->getAssociationName();

        $data = $context->getResult();
        if (\is_array($data) && \array_key_exists($associationName, $data) && \count($data) !== 1) {
            // the form data are already prepared
            return;
        }

        $context->setRequestData([$associationName => $context->getRequestData()]);

        /** @var ShoppingList $shoppingList */
        $shoppingList = $context->getParentEntity();
        $context->setResult([
            $associationName => new ShoppingListItemCollection(
                $shoppingList,
                $this->entityInstantiator,
                $context->getClassName(),
                $context->getConfig(),
                $shoppingList->getLineItems(),
                $this->validator
            )
        ]);
    }
}
