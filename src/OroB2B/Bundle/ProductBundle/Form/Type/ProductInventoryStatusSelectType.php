<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\ReversedTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntitiesToIdsTransformer;

class ProductInventoryStatusSelectType extends AbstractType
{
    const NAME = 'orob2b_product_inventory_status_select';

    const PROD_INVENTORY_STATUS_ENUM_CODE = 'prod_inventory_status';

    /** @var ManagerRegistry */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
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
        return 'oro_enum_choice';
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
