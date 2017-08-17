<?php

namespace Oro\Bundle\PromotionBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormBuilderInterface;

class CouponAddType extends AbstractType implements DataMapperInterface
{
    const NAME = 'oro_promotion_coupon_add';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'coupon',
                CouponAutocompleteType::NAME,
                [
                    'tooltip' => 'oro.promotion.coupon.form.add_type.code.tooltip',
                    'label' => 'oro.promotion.coupon.code.label',
                    'mapped' => false
                ]
            )
            ->add(
                'addedCoupons',
                EntityIdentifierType::NAME,
                [
                    'class' => Coupon::class,
                    'multiple' => true
                ]
            );

        $builder->setDataMapper($this);
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function mapDataToForms($data, $forms)
    {
        if (null === $data) {
            return;
        }

        $forms = iterator_to_array($forms);
        $forms['addedCoupons']->setData($data);
    }

    /**
     * {@inheritdoc}
     */
    public function mapFormsToData($forms, &$data)
    {
        if (null === $data) {
            return;
        }

        $forms = iterator_to_array($forms);
        $data = $forms['addedCoupons']->getData();
    }
}
