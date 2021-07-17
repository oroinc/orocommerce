<?php

namespace Oro\Bundle\PromotionBundle\Form\Type;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CouponAddType extends AbstractType implements DataMapperInterface
{
    const NAME = 'oro_promotion_coupon_add';

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'coupon',
                CouponAutocompleteType::class,
                [
                    'label' => 'oro.promotion.coupon.code.label',
                    'mapped' => false
                ]
            )
            ->add(
                'addedCoupons',
                EntityIdentifierType::class,
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
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'entity',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['entityClass'] = ClassUtils::getClass($options['entity']);
        $view->vars['entityId'] = $this->doctrineHelper->getSingleEntityIdentifier($options['entity']);
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
