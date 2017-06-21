<?php

namespace Oro\Bundle\PromotionBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CronBundle\Form\Type\ScheduleIntervalsCollectionType;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\PromotionSchedule;
use Oro\Bundle\RuleBundle\Form\Type\RuleType;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeCollectionType;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PromotionType extends AbstractType
{
    const NAME = 'oro_promotion';

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
                    'options' => [
                        'data_class' => PromotionSchedule::class,
                    ]
                ]
            )
            ->add(
                'scopes',
                ScopeCollectionType::NAME,
                [
                    'label' => 'oro.promotion.restrictions.label',
                    'required' => false,
                    'entry_options' => [
                        'scope_type' => 'promotion'
                    ],
                ]
            )
            // TODO: remove this temporary solution after BB-10092
            ->add(
                'productsSegment',
                EntityType::class,
                [
                    'label' => false,
                    'class' => Segment::class,
                    'query_builder' => function (EntityRepository $repository) {
                        $qb = $repository
                            ->createQueryBuilder('s')
                            ->andWhere('s.entity = :entity')
                            ->setParameter('entity', Product::class);

                        return $qb;
                    },
                    'choice_label' => 'name',
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
                    'type' => OroRichTextType::NAME,
                    'options' => [
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
