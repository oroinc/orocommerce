<?php

namespace Oro\Bundle\VisibilityBundle\Form\Extension;

use Oro\Bundle\CatalogBundle\Form\Type\CategoryType;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Form\Type\EntityVisibilityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

class CategoryFormExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return CategoryType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                EntityVisibilityType::VISIBILITY,
                EntityVisibilityType::NAME,
                [
                    'data' => $options['data'],
                    EntityVisibilityType::TARGET_ENTITY_FIELD => 'category',
                    EntityVisibilityType::ALL_CLASS => CategoryVisibility::class,
                    EntityVisibilityType::ACCOUNT_GROUP_CLASS => AccountGroupCategoryVisibility::class,
                    EntityVisibilityType::ACCOUNT_CLASS => AccountCategoryVisibility::class,
                ]
            );
    }
}
