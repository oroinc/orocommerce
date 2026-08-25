<?php

namespace Oro\Bundle\ShoppingListBundle\Form\Type;

use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Allows to edit shopping list line item notes.
 */
class LineItemNotesType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'notes',
            TextareaType::class,
            [
                'label' => 'oro.shoppinglist.lineitem.notes.label',
                'required' => false,
                'empty_data' => null,
            ]
        );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => LineItem::class]);
    }
}
