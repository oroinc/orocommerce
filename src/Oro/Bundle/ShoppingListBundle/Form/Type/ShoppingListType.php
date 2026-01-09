<?php

namespace Oro\Bundle\ShoppingListBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for creating and editing shopping lists.
 *
 * This form type provides a simple interface for creating new shopping lists or editing existing ones.
 * It contains a single required field for the shopping list label (name). The form is used in various contexts
 * including the shopping list creation dialog, mass action workflows for adding products to new shopping lists,
 * and the shopping list management interface. Developers can extend this form type to add custom fields
 * or modify the behavior of shopping list creation and editing.
 */
class ShoppingListType extends AbstractType
{
    public const NAME = 'oro_shopping_list_type';

    /** @var  string */
    protected $dataClass;

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('label', TextType::class, [
                'required' => true,
                'label' => 'oro.shoppinglist.create_new_form.input_label'
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass
        ]);
    }

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
