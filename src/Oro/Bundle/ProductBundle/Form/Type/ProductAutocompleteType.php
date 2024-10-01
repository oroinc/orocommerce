<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroAutocompleteType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for Product autocomplete.
 */
class ProductAutocompleteType extends AbstractProductAwareType
{
    const NAME = 'oro_product_autocomplete';

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return OroAutocompleteType::class;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete' => [
                    'route_name' => 'oro_frontend_autocomplete_search',
                    'route_parameters' => [
                        'name' => 'oro_product_visibility_limited',
                    ],
                    'selection_template_twig' =>
                        '@OroProduct/Product/Autocomplete/autocomplete_selection.html.twig',
                    'componentModule' => 'oroproduct/js/app/components/product-autocomplete-component',
                ],
                'attr' => ['spellcheck' => 'false'],
            ]
        );

        parent::configureOptions($resolver);
    }

    #[\Override]
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
