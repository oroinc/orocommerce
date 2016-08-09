<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

use OroB2B\Bundle\ValidationBundle\Validator\Constraints\Integer;

class PriceListSelectWithPriorityType extends AbstractType
{
    const NAME = 'orob2b_pricing_price_list_select_with_priority';

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
                    'label' => 'orob2b.pricing.pricelist.entity_label',
                    'create_enabled' => false,
                    'constraints' => [new NotBlank()],
                ]
            )
            ->add(
                self::PRIORITY_FIELD,
                'integer',
                [
                    'empty_data' => null,
                    'required' => true,
                    'label' => 'orob2b.pricing.pricelist.priority.label',
                    'constraints' => [new NotBlank(), new Integer()],
                ]
            )
            ->add(
                self::MERGE_ALLOWED_FIELD,
                'checkbox',
                [
                    'label' => 'orob2b.pricing.pricelist.merge_allowed.label'
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
}
