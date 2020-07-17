<?php

namespace Oro\Bundle\ShoppingListBundle\Form\Type;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type shopping list owner grid widget.
 */
class ShoppingListOwnerGridType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['shopping_list_id']);
        $resolver->setDefaults([
            'class' => CustomerUser::class,
            'multiple' => false,
            'expanded' => true,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['shopping_list_id'] = $options['shopping_list_id'];
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): string
    {
        return EntityType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'oro_shopping_list_owner_grid';
    }
}
