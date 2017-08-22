<?php

namespace Oro\Bundle\PromotionBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AppliedDiscountRowType extends AbstractType implements DataMapperInterface
{
    const NAME = 'oro_promotion_applied_discount_row';
    const PROMOTION_FIELD = 'promotion';
    const ENABLED_FIELD = 'enabled';

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(self::PROMOTION_FIELD, HiddenType::class);
        $builder->add(self::ENABLED_FIELD, HiddenType::class);

        $builder->setDataMapper($this);
    }

    /**
     * {@inheritdoc}
     */
    public function mapDataToForms($data, $forms)
    {
        /** @var Form[]|\Traversable $forms */
        $forms = iterator_to_array($forms);

        if ($data instanceof AppliedDiscount) {
            if (isset($forms[self::PROMOTION_FIELD])) {
                $forms[self::PROMOTION_FIELD]->setData($this->getPromotionId($data));
            }

            if (isset($forms[self::ENABLED_FIELD])) {
                $forms[self::ENABLED_FIELD]->setData($data->isEnabled());
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function mapFormsToData($forms, &$data)
    {
        /** @var Form[]|\Traversable $forms */
        $forms = iterator_to_array($forms);

        if (!$data->getId()) {
            $promotion = $this->findPromotion($forms[self::PROMOTION_FIELD]->getData());
            if ($promotion) {
                $data->setPromotion($promotion);
            }
        }

        $data->setEnabled((bool)$forms[self::ENABLED_FIELD]->getData());
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AppliedDiscount::class,
        ]);
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
     * @param int $id
     * @return Promotion|null
     */
    private function findPromotion($id)
    {
        return $this->registry
            ->getManagerForClass(Promotion::class)
            ->getRepository(Promotion::class)
            ->find((int)$id);
    }

    /**
     * @param AppliedDiscount $data
     * @return int|null
     */
    private function getPromotionId(AppliedDiscount $data)
    {
        if ($data->getId()) {
            return $data->getSourcePromotionId();
        }

        if ($data->getPromotion()) {
            return $data->getPromotion()->getId();
        }

        return null;
    }
}
