<?php

namespace Oro\Bundle\WebCatalogBundle\Form\Type;

use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Web catalog and content node select form type to configure "Empty Search Result Page" system config setting.
 */
class EmptySearchResultPageSelectSystemConfigType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'webCatalog',
                WebCatalogSelectType::class,
                [
                    'label' => false,
                    'required' => false,
                    'create_enabled' => false,
                ]
            )
            ->add(
                'contentNode',
                ContentNodeFromWebCatalogSelectType::class,
                [
                    'label' => false,
                    'required' => true,
                ]
            );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var ContentNode|null $contentNode */
            $contentNode = $event->getData();
            if ($contentNode instanceof ContentNode) {
                FormUtils::replaceField($event->getForm(), 'webCatalog', ['data' => $contentNode->getWebCatalog()]);
            }
        });

        $builder->addModelTransformer(
            new CallbackTransformer(
                static fn (?ContentNode $value) => ['contentNode' => $value, 'webCatalog' => $value?->getWebCatalog()],
                static fn (?array $value) => $value['contentNode'] ?? null
            )
        );
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['attr']['data-page-component-module'] = 'oroui/js/app/components/view-component';
        $view->vars['attr']['data-page-component-options'] = json_encode([
            'view' => 'orowebcatalog/js/app/views/content-node-from-webcatalog-view',
            'listenedFieldName' => $view['webCatalog']->vars['full_name'],
            'triggeredFieldName' => $view['contentNode']->vars['full_name'],
        ], JSON_THROW_ON_ERROR);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null, 'error_bubbling' => false]);
    }
}
