<?php

namespace Oro\Bundle\CMSBundle\Form\Extension;

use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Oro\Bundle\CMSBundle\Provider\ContentTemplatesForWysiwygPreviewProvider;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * - Defines "content_templates" option;
 * - adds the content templates list to the builder_plugins.content-templates.contentTemplatesData option;
 */
class ContentTemplatesInWysiwygFormExtension extends AbstractTypeExtension
{
    private ContentTemplatesForWysiwygPreviewProvider $contentTemplatesForWysiwygPreviewProvider;

    private AuthorizationCheckerInterface $authorizationChecker;

    private TokenAccessorInterface $tokenAccessor;

    public function __construct(
        ContentTemplatesForWysiwygPreviewProvider $contentTemplatesForWysiwygPreviewProvider,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->contentTemplatesForWysiwygPreviewProvider = $contentTemplatesForWysiwygPreviewProvider;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined(['content_templates'])
            ->setAllowedTypes('content_templates', ['array'])
            ->setDefaults([
                'content_templates' => function (OptionsResolver $innerResolver) {
                    $innerResolver
                        ->setRequired('enabled')
                        ->setDefault('enabled', $this->tokenAccessor->hasUser()
                            && $this->authorizationChecker->isGranted('oro_cms_content_template_view'))
                        ->setAllowedTypes('enabled', 'bool');
                },
            ]);
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $isContentTemplatesEnabled = $options['content_templates']['enabled'] ?? false;
        if ($isContentTemplatesEnabled) {
            $pageComponentOptions = json_decode($view->vars['attr']['data-page-component-options'] ?? '', true);
            $pageComponentOptions['builderPlugins']['content-templates']['jsmodule'] =
                'orocms/js/app/grapesjs/plugins/content-templates';
            $pageComponentOptions['builderPlugins']['content-templates']['contentTemplatesData'] =
                $this->contentTemplatesForWysiwygPreviewProvider->getContentTemplatesList();
            $view->vars['attr']['data-page-component-options'] = json_encode($pageComponentOptions);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [WYSIWYGType::class];
    }
}
