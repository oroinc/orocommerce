<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumChoiceType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntitiesToIdsTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\ReversedTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductInventoryStatusSelectType extends AbstractType
{
    const NAME = 'oro_product_inventory_status_select';

    const PROD_INVENTORY_STATUS_ENUM_CODE = 'prod_inventory_status';

    /** @var ManagerRegistry */
    protected $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $className = ExtendHelper::buildEnumValueClassName(self::PROD_INVENTORY_STATUS_ENUM_CODE);
        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass($className);

        $entitiesToIdsTransformer = new EntitiesToIdsTransformer($em, $className);
        $builder->addModelTransformer(new ReversedTransformer($entitiesToIdsTransformer));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'enum_code' => self::PROD_INVENTORY_STATUS_ENUM_CODE,
                'multiple' => true
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return EnumChoiceType::class;
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
        return static::NAME;
    }
}
