<?php

namespace Oro\Bundle\CatalogBundle\Form\Type;

use Oro\Bundle\CatalogBundle\ContentVariantType\CategoryPageContentVariantType;
use Oro\Component\WebCatalog\Form\AbstractPageVariantType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class CategoryPageVariantType extends AbstractPageVariantType
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

        parent::buildForm($builder, $options);
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
    protected function getPageContentVariantTypeName()
    {
        return CategoryPageContentVariantType::TYPE;
    }
}
