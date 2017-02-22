<?php

namespace Oro\Bundle\PricingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class PriceListSelectWithPriorityType extends AbstractType
{
    const NAME = 'oro_pricing_price_list_select_with_priority';

    const PRICE_LIST_FIELD = 'priceList';
    const PRIORITY_FIELD = 'priority';
    const MERGE_ALLOWED_FIELD = 'mergeAllowed';

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                self::PRICE_LIST_FIELD,
                PriceListSelectType::NAME,
                [
                    'empty_data' => null,
                    'required' => true,
                    'label' => 'oro.pricing.pricelist.entity_label',
                    'create_enabled' => false,
                    'constraints' => [new NotBlank()],
                ]
            )
            ->add(
                self::MERGE_ALLOWED_FIELD,
                'checkbox',
                [
                    'label' => 'oro.pricing.pricelist.merge_allowed.label'
                ]
            );
    }

    /**
     * {@inheritDoc}
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
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'sortable' => true,
            'sortable_property_path' =>  self::PRIORITY_FIELD,
            'allow_extra_fields' => true
        ]);
    }
}
