<?php

namespace OroB2B\Bundle\TaxBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\OroAutocompleteType;
use OroB2B\Bundle\TaxBundle\Entity\ProductTaxCode;

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
        return 'oro_entity_create_or_select_inline';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'orob2b_product_tax_code',
                'grid_name' => 'products-tax-code-select-grid',
                'product_tax_code' => null,
                'product_tax_code_field' => 'taxCode'
            ]
        );

        $resolver->setAllowedTypes('product_tax_code', ['OroB2B\Bundle\TaxBundle\Entity\ProductTaxCode', 'null']);
        $resolver->setAllowedTypes('product_tax_code_field', 'string');
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
     * @return null|ProductTaxCode
     */
    protected function getProductTax(FormInterface $form)
    {
        $options = $form->getConfig()->getOptions();
        $productTaxField = $options['product_tax_code_field'];

        $parent = $form->getParent();
        while ($parent && !$parent->has($productTaxField)) {
            $parent = $parent->getParent();
        }

        if ($parent && $parent->has($productTaxField)) {
            $productTaxData = $parent->get($productTaxField)->getData();
            if ($productTaxData instanceof ProductTaxCode) {
                return $productTaxData;
            }
        }

        /** @var ProductTaxCode $product */
        $productTax = $options['product_tax_code'];
        if ($productTax) {
            return $productTax;
        }

        return null;
    }

    /**
     * @param FormView $view
     * @return null|ProductTaxCode
     */
    protected function getProductTaxFromView(FormView $view)
    {
        $productTax = null;

        if (isset($view->vars['product_tax_code']) && $view->vars['product_tax_code']) {
            $productTax = $view->vars['product_tax_code'];
        } else {
            $parent = $view->parent;
            while ($parent && !isset($parent->vars['product_tax_code'])) {
                $parent = $parent->parent;
            }

            if ($parent && isset($parent->vars['product_tax_code']) && $parent->vars['product_tax_code']) {
                $productTax = $parent->vars['product_tax_code'];
            }
        }

        return $productTax;
    }

    /**
     * @param FormInterface $form
     * @param FormView $view
     * @return null|ProductTaxCode
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
