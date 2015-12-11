<?php

namespace OroB2B\Bundle\TaxBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\OroAutocompleteType;
use OroB2B\Bundle\TaxBundle\Entity\AccountTaxCode;

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
        return 'oro_entity_create_or_select_inline';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'orob2b_account_tax_code',
                'grid_name' => 'accounts-tax-code-select-grid',
                'account_tax_code' => null,
                'account_tax_code_field' => 'taxCode'
            ]
        );

        $resolver->setAllowedTypes('account_tax_code', ['OroB2B\Bundle\TaxBundle\Entity\AccountTaxCode', 'null']);
        $resolver->setAllowedTypes('account_tax_code_field', 'string');
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $accountTax = $this->getAccountTaxFromFormOrView($form, $view);

        if ($accountTax) {
            $view->vars['componentOptions']['accountTax'] = [
                'id' => $accountTax->getId(),
                'description' => $accountTax->getDescription(),
            ];
        }
    }

    /**
     * @param FormInterface $form
     * @return null|AccountTaxCode
     */
    protected function getAccountTax(FormInterface $form)
    {
        $options = $form->getConfig()->getOptions();
        $accountTaxField = $options['account_tax_code_field'];

        $parent = $form->getParent();
        while ($parent && !$parent->has($accountTaxField)) {
            $parent = $parent->getParent();
        }

        if ($parent && $parent->has($accountTaxField)) {
            $accountTaxData = $parent->get($accountTaxField)->getData();
            if ($accountTaxData instanceof AccountTaxCode) {
                return $accountTaxData;
            }
        }

        /** @var AccountTaxCode $account */
        $accountTax = $options['account_tax_code'];
        if ($accountTax) {
            return $accountTax;
        }

        return null;
    }

    /**
     * @param FormView $view
     * @return null|AccountTaxCode
     */
    protected function getAccountTaxFromView(FormView $view)
    {
        $accountTax = null;

        if (isset($view->vars['account_tax_code']) && $view->vars['account_tax_code']) {
            $accountTax = $view->vars['account_tax_code'];
        } else {
            $parent = $view->parent;
            while ($parent && !isset($parent->vars['account_tax_code'])) {
                $parent = $parent->parent;
            }

            if ($parent && isset($parent->vars['account_tax_code']) && $parent->vars['account_tax_code']) {
                $accountTax = $parent->vars['account_tax_code'];
            }
        }

        return $accountTax;
    }

    /**
     * @param FormInterface $form
     * @param FormView $view
     * @return null|AccountTaxCode
     */
    protected function getAccountTaxFromFormOrView(FormInterface $form, FormView $view)
    {
        $accountTax = $this->getAccountTax($form);
        if (!$accountTax) {
            $accountTax = $this->getAccountTaxFromView($view);
        }

        return $accountTax;
    }
}
