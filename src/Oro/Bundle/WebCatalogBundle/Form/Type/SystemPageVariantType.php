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
    public const MENU_NAME = 'frontend_menu';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
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
                    'menu_name' => self::MENU_NAME,
                    'name_filter' => '/^oro_\w+(?<!frontend_root)$/',
                ]
            );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'content_variant_type' => SystemPageContentVariantType::TYPE,
            ]
        );
    }

    #[\Override]
    public function getParent(): ?string
    {
        return PageVariantType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_web_catalog_system_page_variant';
    }
}
