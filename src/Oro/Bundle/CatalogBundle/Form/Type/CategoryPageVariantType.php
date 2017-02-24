<?php

namespace Oro\Bundle\CatalogBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Component\WebCatalog\Form\PageVariantType;
use Oro\Bundle\CatalogBundle\ContentVariantType\CategoryPageContentVariantType;

class CategoryPageVariantType extends AbstractType
{
    const NAME = 'oro_catalog_category_page_variant';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'categoryPageCategory',
                CategoryTreeType::NAME,
                [
                    'label' => 'oro.catalog.category.entity_label',
                    'required' => true,
                    'constraints' => [new NotBlank()]
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return PageVariantType::class;
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
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'content_variant_type' => CategoryPageContentVariantType::TYPE,
        ]);
    }
}
