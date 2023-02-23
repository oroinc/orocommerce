<?php

namespace Oro\Bundle\ShoppingListBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds the submitted value for a shopping list "default" field to the context shared data.
 * This is needed to change the current shopping list after saving the shopping list to the database.
 * @see \Oro\Bundle\ShoppingListBundle\Api\Processor\SaveShoppingListDefault
 */
class HandleShoppingListDefault implements ProcessorInterface
{
    public const SUBMITTED_DEFAULT_VALUES = 'default_shopping_list';

    private const FIELD_NAME = 'default';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $form = $context->getForm();
        if (!$form->has(self::FIELD_NAME) || !FormUtil::isSubmittedAndValid($form)) {
            return;
        }

        $formField = $form->get(self::FIELD_NAME);
        if (!$formField->isSubmitted()) {
            return;
        }

        $sharedData = $context->getSharedData();
        $defaultValues = $sharedData->get(self::SUBMITTED_DEFAULT_VALUES);
        $defaultValues[spl_object_hash($form->getData())] = $formField->getData();
        $sharedData->set(self::SUBMITTED_DEFAULT_VALUES, $defaultValues);
    }
}
