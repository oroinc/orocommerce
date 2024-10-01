<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumChoiceType;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntitiesToIdsTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\ReversedTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form Enum Choice Type for product inventory status.
 *
 * available choices selecting from `oro_enum_option` table by `enum_code`
 */
class ProductInventoryStatusSelectType extends AbstractType
{
    public const NAME = 'oro_product_inventory_status_select';
    public const PROD_INVENTORY_STATUS_ENUM_CODE = 'prod_inventory_status';

    public function __construct(private ManagerRegistry $registry)
    {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass(EnumOption::class);

        $entitiesToIdsTransformer = new EntitiesToIdsTransformer($em, EnumOption::class);
        $builder->addModelTransformer(new ReversedTransformer($entitiesToIdsTransformer));
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'enum_code' => self::PROD_INVENTORY_STATUS_ENUM_CODE,
                'multiple' => true
            ]
        );
    }

    #[\Override]
    public function getParent(): ?string
    {
        return EnumChoiceType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return static::NAME;
    }
}
