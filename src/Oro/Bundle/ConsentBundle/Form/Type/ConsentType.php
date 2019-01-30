<?php

namespace Oro\Bundle\ConsentBundle\Form\Type;

use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\FormBundle\Form\Extension\StripTagsExtension;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Form\Type\WebCatalogSelectType;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Form type that helps edit consents
 */
class ConsentType extends AbstractType
{
    /**
     * @var WebCatalogProvider
     */
    private $webCatalogProvider;

    /**
     * @var FormFactory
     */
    private $factory;

    /**
     * @param WebCatalogProvider $webCatalogProvider
     * @param FormFactory $factory
     */
    public function __construct(WebCatalogProvider $webCatalogProvider, FormFactory $factory)
    {
        $this->webCatalogProvider = $webCatalogProvider;
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => Consent::class
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'names',
                LocalizedFallbackValueCollectionType::class,
                [
                    'label' => 'oro.consent.names.label',
                    'tooltip' => 'oro.consent.names.description',
                    'required' => true,
                    'entry_options' => [
                        'constraints' => [new NotBlank()],
                        StripTagsExtension::OPTION_NAME => true,
                    ],
                ]
            )
            ->add(
                'mandatory',
                ChoiceType::class,
                [
                    'label' => 'oro.consent.type.label',
                    'tooltip' => 'oro.consent.type.description',
                    'choices' => [
                        'oro.consent.type.optional' => false,
                        'oro.consent.type.mandatory' => true
                    ],
                    'choices_as_values' => true,
                    'placeholder' => false,
                ]
            )
            ->add(
                'declinedNotification',
                CheckboxType::class,
                [
                    'label' => 'oro.consent.declined_notification.label',
                    'tooltip' => 'oro.consent.declined_notification.description',
                    'required' => false
                ]
            );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
    }

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $consent = $event->getData();
        $form = $event->getForm();

        if (!$consent->getId()) {
            $consent->setDeclinedNotification(true);
        }

        $this->preSetWebCatalogAndContentNode($consent, $form);
        $event->setData($consent);
    }

    /**
     * @param Consent $consent
     * @param FormInterface $form
     */
    protected function preSetWebCatalogAndContentNode(Consent $consent, FormInterface $form)
    {
        $contentNode = $consent->getContentNode();
        $webCatalog = $contentNode instanceof ContentNode
            ? $contentNode->getWebCatalog()
            : $this->webCatalogProvider->getWebCatalog();

        $form->add(
            $this->factory->createNamed(
                'webcatalog',
                WebCatalogSelectType::class,
                $webCatalog,
                [
                    'label' => 'oro.consent.webcatalog.label',
                    'tooltip' => 'oro.consent.webcatalog.description',
                    'mapped' => false,
                    'auto_initialize' => false,
                    'create_enabled'  => false
                ]
            )
        );

        $contentNodeOptions = [];
        if ($webCatalog instanceof WebCatalog) {
            $contentNodeOptions = [
                'label' => 'oro.consent.content_node.label',
                'tooltip' => 'oro.consent.content_node.description',
                'web_catalog' => $webCatalog,
            ];
        }

        $form->add(
            'content_node',
            ContentNodeSelectType::class,
            $contentNodeOptions
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_consent';
    }
}
