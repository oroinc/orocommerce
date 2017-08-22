<?php

namespace Oro\Bundle\PromotionBundle\Form\Type;

use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\OrderBundle\Form\Type\OrderCollectionTableType;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AppliedDiscountCollectionTableType extends AbstractType
{
    const NAME = 'oro_promotion_applied_discount_collection_table';

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit']);
        $builder->addEventListener(FormEvents::SUBMIT, [$this, 'submit']);
    }

    /**
     * Ensure that applied discounts for same promotion have same enabled status.
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        /** @var PersistentCollection $data */
        $data = $event->getData();

        if (!$data) {
            return;
        }

        $promotionStatuses = [];
        /** @var AppliedDiscount $item */
        foreach ($data as &$item) {
            if (!isset($promotionStatuses[$item['promotion']])) {
                $promotionStatuses[$item['promotion']] = $item['enabled'];
            }
            $item['enabled'] = $promotionStatuses[$item['promotion']];
        }

        $event->setData($data);
    }

    /**
     * Filter out new applied discounts without promotion.
     * @param FormEvent $event
     */
    public function submit(FormEvent $event)
    {
        /** @var PersistentCollection $data */
        $data = $event->getData();

        /** @var AppliedDiscount $item */
        foreach ($data as $key => $item) {
            if (!$item->getId() && !$item->getPromotion()) {
                unset($data[$key]);
            }
        }

        $event->setData($data);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return OrderCollectionTableType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'template_name' => 'OroPromotionBundle:AppliedDiscount:applied_discounts_table.html.twig',
                'page_component' => 'oroui/js/app/components/view-component',
                'page_component_options' => ['view' => 'oropromotion/js/app/views/promotions-view'],
                'attr' => ['class' => 'oro-promotions-collection'],
                'entry_type' => AppliedDiscountRowType::class,
            ]
        );
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
}
