<?php

namespace OroB2B\Bundle\TaxBundle\Form\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\OroAutocompleteType;
use OroB2B\Bundle\TaxBundle\Entity\ProductTax;
use OroB2B\Bundle\TaxBundle\Model\ProductTaxHolderInterface;

class ProductTaxCodeAutocompleteType extends AbstractType
{
    const NAME = 'orob2b_product_tax_code_autocomplete';
    const DATA_PARAMETERS = 'data_parameters';

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
                    'route_name' => 'oro_form_autocomplete_search',
                    'selection_template_twig' =>
                        'OroB2BTaxBundle:Autocomplete:product_tax_autocomplete_selection.html.twig',
                    'componentModule' => 'orob2btax/js/app/components/product-tax-autocomplete-component',
                ],
                'product_tax' => null,
                'product_tax_field' => 'product_tax',
            ]
        );

        $resolver->setAllowedTypes('product_tax', ['OroB2B\Bundle\TaxBundle\Entity\ProductTax', 'null']);
        $resolver->setAllowedTypes('product_tax_field', 'string');
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $productTax = $this->getProductTaxFromFormOrView($form, $view);

        if ($productTax) {
            $view->vars['componentOptions']['productTax'] = [
                'id' => $productTax->getId(),
                'description' => $productTax->getDescription(),
            ];
        }
    }

    /**
     * @param FormInterface $form
     * @return null|ProductTax
     */
    protected function getProductTax(FormInterface $form)
    {
        $options = $form->getConfig()->getOptions();
        $productTaxField = $options['product_tax_field'];

        $parent = $form->getParent();
        while ($parent && !$parent->has($productTaxField)) {
            $parent = $parent->getParent();
        }

        if ($parent && $parent->has($productTaxField)) {
            $productTaxData = $parent->get($productTaxField)->getData();
            if ($productTaxData instanceof ProductTax) {
                return $productTaxData;
            }

            if ($productTaxData instanceof ProductTaxHolderInterface) {
                return $productTaxData->getProductTax();
            }
        }

        /** @var ProductTax $product */
        $productTax = $options['product_tax'];
        if ($productTax) {
            return $productTax;
        }

        /** @var ProductTaxHolderInterface $productTaxHolder */
        $productTaxHolder = $options['product_tax_holder'];
        if ($productTaxHolder) {
            return $productTaxHolder->getProductTax();
        }

        return null;
    }

    /**
     * @param FormView $view
     * @return null|ProductTax
     */
    protected function getProductTaxFromView(FormView $view)
    {
        $productTax = null;

        if (isset($view->vars['product_tax']) && $view->vars['product_tax']) {
            $productTax = $view->vars['product_tax'];
        } else {
            $parent = $view->parent;
            while ($parent && !isset($parent->vars['product_tax'])) {
                $parent = $parent->parent;
            }

            if ($parent && isset($parent->vars['product_tax']) && $parent->vars['product_tax']) {
                $productTax = $parent->vars['product_tax'];
            }
        }

        return $productTax;
    }

    /**
     * @param FormInterface $form
     * @param FormView $view
     * @return null|ProductTax
     */
    protected function getProductTaxFromFormOrView(FormInterface $form, FormView $view)
    {
        $productTax = $this->getProductTax($form);
        if (!$productTax) {
            $productTax = $this->getProductTaxFromView($view);
        }

        return $productTax;
    }

}
