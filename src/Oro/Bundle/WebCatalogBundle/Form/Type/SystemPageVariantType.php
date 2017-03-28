<?php

namespace Oro\Bundle\WebCatalogBundle\Form\Type;

use Oro\Bundle\NavigationBundle\Form\Type\RouteChoiceType;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeCollectionType;
use Oro\Bundle\WebCatalogBundle\ContentVariantType\SystemPageContentVariantType;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
                RouteChoiceType::NAME,
                [
                    'label' => 'oro.webcatalog.contentvariant.system_page_route.label',
                    'required' => true,
                    'options_filter' => [
                        'frontend' => true
                    ],
                    'menu_name' => self::MENU_NAME
                ]
            )
            ->add(
                'scopes',
                ScopeCollectionType::NAME,
                [
                    'label' => 'oro.webcatalog.contentvariant.scopes.label',
                    'required' => false,
                    'entry_options' => [
                        'scope_type' => 'web_content',
                        'web_catalog' => $options['web_catalog']
                    ]
                ]
            )
            ->add(
                'type',
                HiddenType::class,
                [
                    'data' => SystemPageContentVariantType::TYPE
                ]
            )
            ->add(
                'default',
                RadioType::class,
                [
                    'required' => true
                ]
            );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $data = $event->getData();
                if ($data instanceof ContentVariantInterface) {
                    $data->setType(SystemPageContentVariantType::TYPE);
                }
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('web_catalog');
        $resolver->setAllowedTypes('web_catalog', ['null', WebCatalog::class]);

        $resolver->setDefaults(
            [
                'data_class' => ContentVariant::class
            ]
        );
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
