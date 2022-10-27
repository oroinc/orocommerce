<?php

namespace Oro\Bundle\VisibilityBundle\Form\Extension;

use Oro\Bundle\CatalogBundle\Form\Type\CategoryType;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Form\Type\EntityVisibilityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

class CategoryFormExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [CategoryType::class];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                EntityVisibilityType::VISIBILITY,
                EntityVisibilityType::class,
                [
                    'data' => $options['data'],
                    EntityVisibilityType::ALL_CLASS => CategoryVisibility::class,
                    EntityVisibilityType::ACCOUNT_GROUP_CLASS => CustomerGroupCategoryVisibility::class,
                    EntityVisibilityType::ACCOUNT_CLASS => CustomerCategoryVisibility::class,
                ]
            );
    }
}
