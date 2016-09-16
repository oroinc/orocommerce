<?php

namespace Oro\Bundle\PricingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
class PriceAttributePriceListType extends AbstractType
{
    const NAME = 'oro_pricing_price_attribute_price_list';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var PriceAttributePriceList $priceAttributePriceList */
        $priceAttributePriceList = $builder->getData();

        $builder
            ->add('name', 'text', ['required' => true, 'label' => 'oro.pricing.pricelist.name.label'])
            ->add(
                'fieldName',
                'text',
                ['required' => true, 'label' => 'oro.pricing.priceattributepricelist.field_name.label']
            )
            ->add(
                'currencies',
                CurrencySelectionType::NAME,
                [
                    'multiple' => true,
                    'required' => true,
                    'label' => 'oro.pricing.priceattributepricelist.currencies.label',
                    'additional_currencies' => $priceAttributePriceList ?
                        $priceAttributePriceList->getCurrencies() : [],
                ]
            );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass,
            ]
        );
    }

    /**
     * @return string
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
}
