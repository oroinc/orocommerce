<?php

namespace Oro\Bundle\CMSBundle\Controller;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeRegistry;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Form\Handler\ContentWidgetHandler;
use Oro\Bundle\CMSBundle\Form\Type\ContentWidgetType;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
     *
     * @return array
     */
    public function indexAction(): array
    {
        return [
            'entity_class' => ContentWidget::class,
        ];
    }

    /**
     * @Route("/{id}", name="oro_cms_content_widget_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_cms_content_widget_view",
     *      type="entity",
     *      class="OroCMSBundle:ContentWidget",
     *      permission="VIEW"
     * )
     *
     * @param ContentWidget $contentWidget
     * @return array
     */
    public function viewAction(ContentWidget $contentWidget): array
    {
        $contentWidgetType = $this->get(ContentWidgetTypeRegistry::class)
            ->getWidgetType($contentWidget->getWidgetType());

        $translator = $this->get(TranslatorInterface::class);

        $additionalBlocks = $contentWidgetType
            ? $contentWidgetType->getBackOfficeViewSubBlocks($contentWidget, $this->get('twig'))
            : [];

        foreach ($additionalBlocks as &$additionalBlock) {
            $additionalBlock['title'] = $translator->trans($additionalBlock['title']);
        }

        return [
            'entity' => $contentWidget,
            'additionalBlocks' => $additionalBlocks
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
        $contentWidget = new ContentWidget();
        $contentWidget->setWidgetType('copyright'); // @todo get widget type dynamically from the form

        return $this->update($contentWidget);
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
        $contentWidgetType = $this->get(ContentWidgetTypeRegistry::class)
            ->getWidgetType($contentWidget->getWidgetType());

        $contentWidgetForm = $contentWidgetType
            ? $contentWidgetType->getSettingsForm($contentWidget, $this->get('form.factory'))
            : null;

        $form = $this->createForm(
            ContentWidgetType::class,
            $contentWidget,
            ['widget_type_form' => $contentWidgetForm]
        );

        return $this->get(UpdateHandlerFacade::class)
            ->update(
                $contentWidget,
                $form,
                $this->get(TranslatorInterface::class)->trans('oro.cms.controller.contentwidget.saved.message'),
                null,
                $this->get(ContentWidgetHandler::class)
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
            ]
        );
    }
}
