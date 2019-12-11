<?php

namespace Oro\Bundle\WebCatalogBundle\Form\Extension;

use Oro\Bundle\ScopeBundle\Form\Type\ScopeCollectionType;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Oro\Component\WebCatalog\Form\PageVariantType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PageVariantTypeExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [PageVariantType::class];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $pageContentVariantTypeName = $options['content_variant_type'];

        $builder
            ->add(
                'scopes',
                ScopeCollectionType::class,
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
                    'data' => $pageContentVariantTypeName
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
            function (FormEvent $event) use ($pageContentVariantTypeName) {
                $data = $event->getData();
                if ($data instanceof ContentVariantInterface) {
                    $data->setType($pageContentVariantTypeName);
                }
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['web_catalog', 'content_variant_type']);
        $resolver->setAllowedTypes('web_catalog', ['null', WebCatalog::class]);

        $resolver->setDefault('data_class', ContentVariant::class);
    }
}
