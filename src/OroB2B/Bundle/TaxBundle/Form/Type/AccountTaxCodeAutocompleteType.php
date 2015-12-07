<?php

namespace OroB2B\Bundle\TaxBundle\Form\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\OroAutocompleteType;
use OroB2B\Bundle\TaxBundle\Entity\AccountTax;

class AccountTaxCodeAutocompleteType extends AbstractType
{
    const NAME = 'orob2b_account_tax_code_autocomplete';
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
                    'route_name' => 'orob2b_account_tax_autocomplete_search',
                    'route_parameters' => [
                        'name' => 'orob2b_account_tax_visibility_limited',
                    ],
                    'selection_template_twig' =>
                        'OroB2BTaxBundle:Autocomplete:account_tax_autocomplete_selection.html.twig',
                    'componentModule' => 'orob2btax/js/app/components/account-tax-autocomplete-component',
                ],
                'account_tax' => null,
                'account_tax_field' => 'account_tax',
            ]
        );

        $resolver->setAllowedTypes('account_tax', ['OroB2B\Bundle\TaxBundle\Entity\AccountTax', 'null']);
        $resolver->setAllowedTypes('account_tax_field', 'string');
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $productTax = $this->getAccountTaxFromFormOrView($form, $view);

        if ($productTax) {
            $view->vars['componentOptions']['productTax'] = [
                'id' => $productTax->getId(),
                'description' => $productTax->getDescription(),
            ];
        }
    }

    /**
     * @param FormInterface $form
     * @return null|AccountTax
     */
    protected function getAccountTax(FormInterface $form)
    {
        $options = $form->getConfig()->getOptions();
        $productTaxField = $options['account_tax_field'];

        $parent = $form->getParent();
        while ($parent && !$parent->has($productTaxField)) {
            $parent = $parent->getParent();
        }

        if ($parent && $parent->has($productTaxField)) {
            $productTaxData = $parent->get($productTaxField)->getData();
            if ($productTaxData instanceof AccountTax) {
                return $productTaxData;
            }

            if ($productTaxData instanceof AccountTaxHolderInterface) {
                return $productTaxData->getAccountTax();
            }
        }

        /** @var AccountTax $product */
        $productTax = $options['account_tax'];
        if ($productTax) {
            return $productTax;
        }

        /** @var AccountTaxHolderInterface $productTaxHolder */
        $productTaxHolder = $options['account_tax_holder'];
        if ($productTaxHolder) {
            return $productTaxHolder->getAccountTax();
        }

        return null;
    }

    /**
     * @param FormView $view
     * @return null|AccountTax
     */
    protected function getAccountTaxFromView(FormView $view)
    {
        $productTax = null;

        if (isset($view->vars['account_tax']) && $view->vars['account_tax']) {
            $productTax = $view->vars['account_tax'];
        } else {
            $parent = $view->parent;
            while ($parent && !isset($parent->vars['account_tax'])) {
                $parent = $parent->parent;
            }

            if ($parent && isset($parent->vars['account_tax']) && $parent->vars['account_tax']) {
                $productTax = $parent->vars['account_tax'];
            }
        }

        return $productTax;
    }

    /**
     * @param FormInterface $form
     * @param FormView $view
     * @return null|AccountTax
     */
    protected function getAccountTaxFromFormOrView(FormInterface $form, FormView $view)
    {
        $productTax = $this->getAccountTax($form);
        if (!$productTax) {
            $productTax = $this->getAccountTaxFromView($view);
        }

        return $productTax;
    }

}
