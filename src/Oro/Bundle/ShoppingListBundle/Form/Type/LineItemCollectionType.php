<?php

namespace Oro\Bundle\ShoppingListBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class LineItemCollectionType extends AbstractType
{
    const NAME = 'oro_shopping_list_line_item_collection';

    const LINE_ITEMS_FIELD_NAME = 'lineItems';

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(self::LINE_ITEMS_FIELD_NAME, CollectionType::NAME, [
            'type' => LineItemType::NAME,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
