<?php

namespace OroB2B\Bundle\TaxBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductTaxCodeMultiSelectType extends AbstractType
{
    const NAME = 'orob2b_product_tax_code_multiselect';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_jqueryselect2_hidden';
    }

    /** {@inheritdoc} */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $data = $event->getData();
                if (!is_array($data)) {
                    $data = (array)$data;
                }

                $data = array_filter(
                    $data,
                    function ($value) {
                        return false !== filter_var($value, FILTER_VALIDATE_INT);
                    }
                );

                $data = array_map('intval', $data);

                $event->setData($data);
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => ProductTaxCodeAutocompleteType::AUTOCOMPLETE_ALIAS,
                'configs' => ['multiple' => true],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['attr'] = ['data-selected-data' => json_encode($form->getData())];
    }
}
