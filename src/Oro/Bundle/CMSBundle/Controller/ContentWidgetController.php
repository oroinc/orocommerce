<?php

namespace Oro\Bundle\CMSBundle\Controller;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeRegistry;
use Oro\Bundle\CMSBundle\ContentWidget\FrontendEmulator;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Form\Handler\ContentWidgetHandler;
use Oro\Bundle\CMSBundle\Form\Type\ContentWidgetType;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CMS Content Widget Controller
 */
class ContentWidgetController extends AbstractController
{
    /**
     * @Route("/", name="oro_cms_content_widget_index")
     * @Template
     * @AclAncestor("oro_cms_content_widget_view")
     */
    public function indexAction(): array
    {
        return [
            'entity_class' => ContentWidget::class,
        ];
    }

    /**
     * @Route("/view/{id}", name="oro_cms_content_widget_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_cms_content_widget_view",
     *      type="entity",
     *      class="OroCMSBundle:ContentWidget",
     *      permission="VIEW"
     * )
     */
    public function viewAction(ContentWidget $contentWidget): array
    {
        $contentWidgetType = $this->get(ContentWidgetTypeRegistry::class)
            ->getWidgetType($contentWidget->getWidgetType());

        $additionalBlocks = [];
        if (null !== $contentWidgetType) {
            $twig = $this->get('twig');
            $frontendEmulator = $this->get(FrontendEmulator::class);
            $frontendEmulator->startFrontendRequestEmulation();
            try {
                $additionalBlocks = $contentWidgetType->getBackOfficeViewSubBlocks($contentWidget, $twig);
            } finally {
                $frontendEmulator->stopFrontendRequestEmulation();
            }
        }

        $translator = $this->get(TranslatorInterface::class);
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
     * @Route("/create", name="oro_cms_content_widget_create")
     * @Template("@OroCMS/ContentWidget/update.html.twig")
     * @Acl(
     *      id="oro_cms_content_widget_create",
     *      type="entity",
     *      class="OroCMSBundle:ContentWidget",
     *      permission="CREATE"
     * )
     *
     * @return array|RedirectResponse
     */
    public function createAction()
    {
        return $this->update(new ContentWidget());
    }

    /**
     * @Route("/update/{id}", name="oro_cms_content_widget_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_cms_content_widget_update",
     *      type="entity",
     *      class="OroCMSBundle:ContentWidget",
     *      permission="EDIT"
     * )
     *
     * @param ContentWidget $contentWidget
     * @return array|RedirectResponse
     */
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
        return $this->get(UpdateHandlerFacade::class)
            ->update(
                $contentWidget,
                $this->createForm(ContentWidgetType::class, $contentWidget),
                $this->get(TranslatorInterface::class)->trans('oro.cms.controller.contentwidget.saved.message'),
                null,
                $this->get(ContentWidgetHandler::class),
                function (ContentWidget $contentWidget, FormInterface $form, Request $request) {
                    $updateMarker = $request->get(ContentWidgetHandler::UPDATE_MARKER, false);
                    if ($updateMarker) {
                        $form = $this->createForm(ContentWidgetType::class, $contentWidget);
                    }

                    $contentWidgetType = $contentWidget->getWidgetType()
                        ? $this->get(ContentWidgetTypeRegistry::class)->getWidgetType($contentWidget->getWidgetType())
                        : null;

                    return [
                        'form' => $form->createView(),
                        'contentWidgetType' => $contentWidgetType,
                    ];
                }
            );
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
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
