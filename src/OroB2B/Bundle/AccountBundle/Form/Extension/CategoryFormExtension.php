<?php

namespace OroB2B\Bundle\AccountBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\CatalogBundle\Form\Type\CategoryType;

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
                'categoryVisibility',
                'oro_enum_select',
                [
                    'required' => false,
                    'mapped' => false,
                    'label' => 'orob2b.account.categoryvisibility.entity_label',
                    'enum_code' => 'category_visibility',
                    'configs'   => [
                        'allowClear' => false,
                    ]
                ]
            );
    }
}
