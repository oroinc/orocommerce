<?php

namespace Oro\Bundle\PromotionBundle\Form\Type;

use Oro\Bundle\CronBundle\Form\Type\ScheduleIntervalsCollectionType;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\ProductBundle\Form\Type\ProductCollectionSegmentType;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\PromotionSchedule;
use Oro\Bundle\RuleBundle\Form\Type\RuleType;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeCollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;

class PromotionType extends AbstractType
{
    const NAME = 'oro_promotion';
    const SCOPE_TYPE = 'promotion';

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('rule', RuleType::class, ['name_tooltip' => 'oro.promotion.name.tooltip'])
            ->add(
                'useCoupons',
                ChoiceType::class,
                [
                    'label' => 'oro.promotion.use_coupons.label',
                    'required' => false,
                    'choices' => [
                        false => 'oro.promotion.use_coupons.no',
                        true => 'oro.promotion.use_coupons.yes',
                    ],
                    'empty_value' => false,
                ]
            )
            ->add('discountConfiguration', DiscountConfigurationType::NAME)
            ->add(
                'schedules',
                ScheduleIntervalsCollectionType::NAME,
                [
                    'label' => 'oro.promotion.dates.label',
                    'entry_options' => [
                        'data_class' => PromotionSchedule::class,
                    ]
                ]
            )
            ->add(
                'scopes',
                ScopeCollectionType::NAME,
                [
                    'label' => 'oro.promotion.restrictions.label',
                    'required' => true,
                    'constraints' => [new Count(['min' => 1])],
                    'entry_options' => [
                        'scope_type' => self::SCOPE_TYPE
                    ],
                ]
            )
            ->add(
                'productsSegment',
                ProductCollectionSegmentType::NAME,
                [
                    'segment_name_template' => 'Promotion Matching Products %s'
                ]
            )
            ->add(
                'labels',
                LocalizedFallbackValueCollectionType::NAME,
                [
                    'label' => 'oro.promotion.labels.label',
                    'tooltip' => 'oro.promotion.labels.tooltip',
                    'required' => false,
                ]
            )
            ->add(
                'descriptions',
                LocalizedFallbackValueCollectionType::NAME,
                [
                    'label' => 'oro.promotion.descriptions.label',
                    'tooltip' => 'oro.promotion.descriptions.tooltip',
                    'required' => false,
                    'field' => 'text',
                    'entry_type' => OroRichTextType::NAME,
                    'entry_options' => [
                        'wysiwyg_options' => [
                            'statusbar' => true,
                            'resize' => true,
                        ],
                    ],
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Promotion::class,
        ]);
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
