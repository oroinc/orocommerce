<?php

namespace Oro\Bundle\CMSBundle\Controller;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeRegistry;
use Oro\Bundle\CMSBundle\ContentWidget\FrontendEmulator;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Form\Handler\ContentWidgetHandler;
use Oro\Bundle\CMSBundle\Form\Type\ContentWidgetType;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CMS Content Widget Controller
 */
class ContentWidgetController extends AbstractController
{
    #[Route(path: '/', name: 'oro_cms_content_widget_index')]
    #[Template('@OroCMS/ContentWidget/index.html.twig')]
    #[AclAncestor('oro_cms_content_widget_view')]
    public function indexAction(): array
    {
        return [
            'entity_class' => ContentWidget::class,
        ];
    }

    #[Route(path: '/view/{id}', name: 'oro_cms_content_widget_view', requirements: ['id' => '\d+'])]
    #[Template('@OroCMS/ContentWidget/view.html.twig')]
    #[Acl(id: 'oro_cms_content_widget_view', type: 'entity', class: ContentWidget::class, permission: 'VIEW')]
    public function viewAction(ContentWidget $contentWidget): array
    {
        $contentWidgetType = $this->container->get(ContentWidgetTypeRegistry::class)
            ->getWidgetType($contentWidget->getWidgetType());

        $additionalBlocks = [];
        if (null !== $contentWidgetType) {
            $twig = $this->container->get('twig');
            $frontendEmulator = $this->container->get(FrontendEmulator::class);
            $frontendEmulator->startFrontendRequestEmulation();
            try {
                $additionalBlocks = $contentWidgetType->getBackOfficeViewSubBlocks($contentWidget, $twig);
            } finally {
                $frontendEmulator->stopFrontendRequestEmulation();
            }
        }

        $translator = $this->container->get(TranslatorInterface::class);
        foreach ($additionalBlocks as &$additionalBlock) {
            $additionalBlock['title'] = isset($additionalBlock['title'])
                ? $translator->trans((string) $additionalBlock['title'])
                : '';
        }

        return [
            'entity' => $contentWidget,
            'additionalBlocks' => $additionalBlocks,
            'contentWidgetType' => $contentWidgetType,
        ];
    }

    /**
     *
     * @return array|RedirectResponse
     */
    #[Route(path: '/create', name: 'oro_cms_content_widget_create')]
    #[Template('@OroCMS/ContentWidget/update.html.twig')]
    #[Acl(id: 'oro_cms_content_widget_create', type: 'entity', class: ContentWidget::class, permission: 'CREATE')]
    public function createAction()
    {
        return $this->update(new ContentWidget());
    }

    /**
     * @param ContentWidget $contentWidget
     * @return array|RedirectResponse
     */
    #[Route(path: '/update/{id}', name: 'oro_cms_content_widget_update', requirements: ['id' => '\d+'])]
    #[Template('@OroCMS/ContentWidget/update.html.twig')]
    #[Acl(id: 'oro_cms_content_widget_update', type: 'entity', class: ContentWidget::class, permission: 'EDIT')]
    public function updateAction(ContentWidget $contentWidget)
    {
        return $this->update($contentWidget);
    }

    /**
     * @param ContentWidget $contentWidget
     * @return array|RedirectResponse
     */
    protected function update(ContentWidget $contentWidget)
    {
        return $this->container->get(UpdateHandlerFacade::class)
            ->update(
                $contentWidget,
                $this->createForm(ContentWidgetType::class, $contentWidget),
                $this->container->get(TranslatorInterface::class)
                    ->trans('oro.cms.controller.contentwidget.saved.message'),
                null,
                $this->container->get(ContentWidgetHandler::class),
                function (ContentWidget $contentWidget, FormInterface $form, Request $request) {
                    $updateMarker = $request->get(ContentWidgetHandler::UPDATE_MARKER, false);
                    if ($updateMarker) {
                        $form = $this->createForm(ContentWidgetType::class, $contentWidget);
                    }

                    $contentWidgetType = $contentWidget->getWidgetType()
                        ? $this->container->get(ContentWidgetTypeRegistry::class)
                            ->getWidgetType($contentWidget->getWidgetType())
                        : null;

                    return [
                        'form' => $form->createView(),
                        'contentWidgetType' => $contentWidgetType,
                    ];
                }
            );
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                UpdateHandlerFacade::class,
                TranslatorInterface::class,
                ContentWidgetTypeRegistry::class,
                ContentWidgetHandler::class,
                FrontendEmulator::class
            ]
        );
    }
}
