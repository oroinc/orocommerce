<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;

class PriceListType extends AbstractType
{
    const NAME = 'orob2b_pricing_price_list';
    const SCHEDULES_FIELD = 'schedules';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @var string
     */
    protected $accountClass;

    /**
     * @var string
     */
    protected $accountGroupClass;

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
     * @param string $accountClass
     */
    public function setAccountClass($accountClass)
    {
        $this->accountClass = $accountClass;
    }

    /**
     * @param string $accountGroupClass
     */
    public function setAccountGroupClass($accountGroupClass)
    {
        $this->accountGroupClass = $accountGroupClass;
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
                self::SCHEDULES_FIELD,
                CollectionType::NAME,
                [
                    'type' => PriceListScheduleType::NAME,
                    'by_reference' => false,
                    'required' => false,
                ]
            )
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
            ->add('active', 'checkbox', ['label' => 'orob2b.pricing.pricelist.active.label']);
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
