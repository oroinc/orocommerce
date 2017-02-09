<?php

namespace Oro\Bundle\WebCatalogBundle\Form\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Component\WebCatalog\Form\PageVariantType;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Oro\Component\WebCatalog\Entity\WebCatalogInterface;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeCollectionType;

class PageVariantTypeExtension extends AbstractTypeExtension
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return PageVariantType::class;
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
        $em = $this->registry->getManager();

        $resolver->setRequired(['web_catalog', 'content_variant_type']);
        $resolver->setAllowedTypes(
            'web_catalog',
            [
                'null',
                $em->getClassMetadata(WebCatalogInterface::class)->getName()
            ]
        );

        $resolver->setDefaults(
            [
                'data_class' => $em->getClassMetadata(ContentVariantInterface::class)->getName()
            ]
        );
    }
}
