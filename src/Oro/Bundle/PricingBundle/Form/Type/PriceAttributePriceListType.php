<?php

namespace Oro\Bundle\PricingBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for product price attribute entity.
 */
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

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var PriceAttributePriceList $priceAttributePriceList */
        $priceAttributePriceList = $builder->getData();

        $builder
            ->add('name', TextType::class, ['required' => true, 'label' => 'oro.pricing.pricelist.name.label'])
            ->add(
                'fieldName',
                TextType::class,
                ['required' => true, 'label' => 'oro.pricing.priceattributepricelist.field_name.label']
            )
            ->add(
                'currencies',
                CurrencySelectionType::class,
                [
                    'multiple' => true,
                    'required' => true,
                    'label' => 'oro.pricing.priceattributepricelist.currencies.label',
                    'additional_currencies' => $priceAttributePriceList ?
                        $priceAttributePriceList->getCurrencies() : [],
                ]
            )
            ->add(
                'enabledInExport',
                ChoiceType::class,
                [
                    'label' => 'oro.pricing.priceattributepricelist.enabled_in_export.label',
                    'choices' => [
                        'oro.pricing.form.priceattributepricelist.enabled_in_export.no' => 0,
                        'oro.pricing.form.priceattributepricelist.enabled_in_export.yes' => 1
                    ],
                    'placeholder' => false,
                ]
            );
    }

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
