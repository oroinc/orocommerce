<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\OroAutocompleteType;

class ProductAutocompleteType extends AbstractProductAwareType
{
    const NAME = 'orob2b_product_autocomplete';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return OroAutocompleteType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete' => [
                    'route_name' => 'orob2b_frontend_autocomplete_search',
                    'route_parameters' => [
                        'name' => 'orob2b_product_visibility_limited',
                    ],
                    'selection_template_twig' =>
                        'OroB2BProductBundle:Product:Autocomplete/autocomplete_selection.html.twig',
                    'componentModule' => 'orob2bproduct/js/app/components/product-autocomplete-component',
                ],
                'attr' => ['spellcheck' => 'false'],
            ]
        );

        parent::configureOptions($resolver);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $product = $this->getProductFromFormOrView($form, $view);

        if ($product) {
            $view->vars['componentOptions']['product'] = [
                'sku' => $product->getSku(),
                'name' => (string)$product->getDefaultName(),
            ];
        }
    }
}
