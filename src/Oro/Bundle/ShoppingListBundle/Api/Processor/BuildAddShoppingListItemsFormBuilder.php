<?php

namespace Oro\Bundle\ShoppingListBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Form\FormHelper;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresource\BuildCollectionFormBuilder;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresourceContext;
use Oro\Bundle\ShoppingListBundle\Api\Model\ShoppingListItemCollection;
use Symfony\Component\Form\FormInterface;

/**
 * Builds the form builder for "add to cart" sub-resource and sets it to the context.
 */
class BuildAddShoppingListItemsFormBuilder extends BuildCollectionFormBuilder
{
    public function __construct(FormHelper $formHelper)
    {
        parent::__construct($formHelper, true);
    }

    /**
     * {@inheritDoc}
     */
    protected function getEntryFormOptions(ChangeSubresourceContext $context): array
    {
        $entryFormOptions = parent::getEntryFormOptions($context);
        $entryFormOptions['empty_data'] = function (FormInterface $form) {
            /** @var ShoppingListItemCollection $collection */
            $collection = $form->getParent()->getNormData();

            return $collection->getItem($form);
        };

        return $entryFormOptions;
    }
}
