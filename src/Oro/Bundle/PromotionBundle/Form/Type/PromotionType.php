<?php

namespace Oro\Bundle\PromotionBundle\Form\Type;

use Oro\Bundle\CronBundle\Form\Type\ScheduleIntervalsCollectionType;
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

/**
 * Provides functionality to create or edit a Promotion entity
 */
class PromotionType extends AbstractType
{
    public const NAME = 'oro_promotion';
    public const SCOPE_TYPE = 'promotion';

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     */
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('rule', RuleType::class, [
                'name_tooltip' => 'oro.promotion.name.tooltip',
                'enabled_tooltip' => 'oro.promotion.enabled.tooltip',
                'sortOrder_tooltip' => 'oro.promotion.sort_order.tooltip',
                'stopProcessing_tooltip' => 'oro.promotion.stop_processing.tooltip'
            ])
            ->add(
                'useCoupons',
                ChoiceType::class,
                [
                    'label' => 'oro.promotion.use_coupons.label',
                    'tooltip' => 'oro.promotion.use_coupons.tooltip',
                    'required' => false,
                    'choices' => [
                        'oro.promotion.use_coupons.no' => 0,
                        'oro.promotion.use_coupons.yes' => 1,
                    ],
                    'placeholder' => false,
                ]
            )
            ->add('discountConfiguration', DiscountConfigurationType::class)
            ->add(
                'schedules',
                ScheduleIntervalsCollectionType::class,
                [
                    'label' => 'oro.promotion.dates.label',
                    'entry_options' => [
                        'data_class' => PromotionSchedule::class,
                    ]
                ]
            )
            ->add(
                'scopes',
                ScopeCollectionType::class,
                [
                    'label' => 'oro.promotion.restrictions.label',
                    'required' => false,
                    'entry_options' => [
                        'scope_type' => self::SCOPE_TYPE
                    ],
                ]
            )
            ->add(
                'productsSegment',
                ProductCollectionSegmentType::class,
                [
                    'segment_name_template' => 'Promotion Matching Products %s'
                ]
            )
            ->add(
                'labels',
                LocalizedFallbackValueCollectionType::class,
                [
                    'label' => 'oro.promotion.labels.label',
                    'tooltip' => 'oro.promotion.labels.tooltip',
                    'required' => false,
                ]
            );
    }

    #[\Override]
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

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
