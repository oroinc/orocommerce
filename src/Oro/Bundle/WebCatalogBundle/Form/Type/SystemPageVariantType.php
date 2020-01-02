<?php

namespace Oro\Bundle\WebCatalogBundle\Form\Type;

use Oro\Bundle\NavigationBundle\Form\Type\RouteChoiceType;
use Oro\Bundle\WebCatalogBundle\ContentVariantType\SystemPageContentVariantType;
use Oro\Component\WebCatalog\Form\PageVariantType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for the System Page content variant
 */
class SystemPageVariantType extends AbstractType
{
    const NAME = 'oro_web_catalog_system_page_variant';
    const MENU_NAME = 'frontend_menu';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'systemPageRoute',
                RouteChoiceType::class,
                [
                    'label' => 'oro.webcatalog.contentvariant.system_page_route.label',
                    'required' => true,
                    'options_filter' => [
                        'frontend' => true
                    ],
                    'menu_name' => self::MENU_NAME
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'content_variant_type' => SystemPageContentVariantType::TYPE,
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
}
