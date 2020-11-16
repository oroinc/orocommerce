<?php

namespace Oro\Bundle\ShoppingListBundle\Form\Type;

use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Allows to edit shopping list notes.
 */
class ShoppingListNotesType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'notes',
            TextareaType::class,
            [
                'label' => 'oro.shoppinglist.notes.label',
                'required' => false,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => ShoppingList::class,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'oro_shopping_list_notes_type';
    }
}
