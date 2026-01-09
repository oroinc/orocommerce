<?php

namespace Oro\Bundle\WebCatalogBundle\Form\Type;

use Oro\Bundle\WebCatalogBundle\ContentVariantType\ContentVariantTypeRegistry;
use Oro\Bundle\WebCatalogBundle\Form\EventListener\ContentVariantCollectionResizeSubscriber;
use Oro\Bundle\WebCatalogBundle\Model\ContentVariantFormPrototype;
use Symfony\Bridge\Doctrine\Form\EventListener\MergeDoctrineCollectionListener;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for managing a collection of content variants within a content node.
 *
 * This form type handles the complex collection of content variants that can be assigned to a web catalog
 * content node. It dynamically generates form prototypes for each available content variant type (system page,
 * landing page, category, product page, product collection), allowing users to add multiple variants with
 * different types and scopes. The form manages the lifecycle of variant forms through event subscribers.
 */
class ContentVariantCollectionType extends AbstractType
{
    public const NAME = 'oro_web_catalog_content_variant_collection';

    /**
     * @var ContentVariantTypeRegistry
     */
    private $variantTypeRegistry;

    public function __construct(ContentVariantTypeRegistry $variantTypeRegistry)
    {
        $this->variantTypeRegistry = $variantTypeRegistry;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('prototype_name', '__variant_idx__');
        $resolver->setDefault('entry_options', []);
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['prototype_name'] = $options['prototype_name'];
        $view->vars['prototypes'] = [];
        $formConfig = $form->getConfig();
        if ($formConfig->hasAttribute('prototypes')) {
            /** @var ContentVariantFormPrototype[] $prototype */
            $prototype = $formConfig->getAttribute('prototypes');

            foreach ($prototype as $name => $prototypeData) {
                $view->vars['prototypes'][$name] = [
                    'title' => $prototypeData->getTitle(),
                    'form' => $prototypeData->getForm()->setParent($form)->createView($view)
                ];
            }
        }
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->initializeContentVariantPrototypes($builder, $options);

        $builder->addEventSubscriber(new MergeDoctrineCollectionListener());
        $builder->addEventSubscriber(
            new ContentVariantCollectionResizeSubscriber($this->variantTypeRegistry, $options['entry_options'])
        );
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return static::NAME;
    }

    protected function initializeContentVariantPrototypes(FormBuilderInterface $builder, array $options)
    {
        $prototypes = [];
        $prototypeOptions = array_replace(['required' => $options['required']], $options['entry_options']);
        foreach ($this->variantTypeRegistry->getAllowedContentVariantTypes() as $contentVariantType) {
            $prototypeForm = $builder
                ->create(
                    $options['prototype_name'],
                    $contentVariantType->getFormType(),
                    $prototypeOptions
                )
                ->getForm();
            $prototypes[$contentVariantType->getName()] = new ContentVariantFormPrototype(
                $prototypeForm,
                $contentVariantType->getTitle()
            );
        }
        $builder->setAttribute('prototypes', $prototypes);
    }
}
