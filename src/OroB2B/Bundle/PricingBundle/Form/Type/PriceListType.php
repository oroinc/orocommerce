<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;

class PriceListType extends AbstractType
{
    const NAME = 'orob2b_pricing_price_list';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @var string
     */
    protected $customerClass;

    /**
     * @var string
     */
    protected $customerGroupClass;

    /**
     * @var string
     */
    protected $websiteClass;

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * @param string $customerClass
     */
    public function setCustomerClass($customerClass)
    {
        $this->customerClass = $customerClass;
    }

    /**
     * @param string $customerGroupClass
     */
    public function setCustomerGroupClass($customerGroupClass)
    {
        $this->customerGroupClass = $customerGroupClass;
    }

    /**
     * @param string $websiteClass
     */
    public function setWebsiteClass($websiteClass)
    {
        $this->websiteClass = $websiteClass;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var PriceList $priceList */
        $priceList = $builder->getData();

        $builder
            ->add('name', 'text', ['required' => true, 'label' => 'orob2b.pricing.pricelist.name.label'])
            ->add(
                'currencies',
                CurrencySelectionType::NAME,
                [
                    'multiple' => true,
                    'required' => true,
                    'label' => 'orob2b.pricing.pricelist.currencies.label',
                    'additional_currencies' => $priceList ? $priceList->getCurrencies() : [],
                ]
            )
            ->add(
                'appendCustomers',
                EntityIdentifierType::NAME,
                [
                    'class' => $this->customerClass,
                    'required' => false,
                    'mapped' => false,
                    'multiple' => true,
                ]
            )
            ->add(
                'removeCustomers',
                EntityIdentifierType::NAME,
                [
                    'class' => $this->customerClass,
                    'required' => false,
                    'mapped' => false,
                    'multiple' => true,
                ]
            )
            ->add(
                'appendCustomerGroups',
                EntityIdentifierType::NAME,
                [
                    'class' => $this->customerGroupClass,
                    'required' => false,
                    'mapped' => false,
                    'multiple' => true,
                ]
            )
            ->add(
                'removeCustomerGroups',
                EntityIdentifierType::NAME,
                [
                    'class' => $this->customerGroupClass,
                    'required' => false,
                    'mapped' => false,
                    'multiple' => true,
                ]
            )
            ->add(
                'appendWebsites',
                EntityIdentifierType::NAME,
                [
                    'class' => $this->websiteClass,
                    'required' => false,
                    'mapped' => false,
                    'multiple' => true,
                ]
            )
            ->add(
                'removeWebsites',
                EntityIdentifierType::NAME,
                [
                    'class' => $this->websiteClass,
                    'required' => false,
                    'mapped' => false,
                    'multiple' => true,
                ]
            );
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }
}
