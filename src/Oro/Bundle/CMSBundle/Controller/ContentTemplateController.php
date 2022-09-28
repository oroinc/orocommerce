<?php

namespace Oro\Bundle\CMSBundle\Controller;

use Oro\Bundle\CMSBundle\Entity\ContentTemplate;
use Oro\Bundle\CMSBundle\Form\Handler\ContentTemplateHandler;
use Oro\Bundle\CMSBundle\Form\Type\ContentTemplateType;
use Oro\Bundle\CMSBundle\Provider\ContentTemplateContentProvider;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD actions for ContentTemplate entity
 */
class ContentTemplateController extends AbstractController
{
    /**
     * @Route("/", name="oro_cms_content_template_index")
     * @Template
     * @Acl(
     *     id="oro_cms_content_template_view",
     *     type="entity",
     *     class="OroCMSBundle:ContentTemplate",
     *     permission="VIEW"
     * )
     */
    public function indexAction(): array
    {
        return [
            'entity_class' => ContentTemplate::class
        ];
    }

    /**
     * @Route("/view/{id}", name="oro_cms_content_template_view", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("oro_cms_content_template_view")
     */
    public function viewAction(ContentTemplate $template): array
    {
        return [
            'entity' => $template
        ];
    }

    /**
     * @Route("/widget/{id}", name="oro_cms_content_template_widget", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("oro_cms_content_template_view")
     */
    public function widgetAction(ContentTemplate $template): array
    {
        return [
            'entity' => $template
        ];
    }

    /**
     * @Route("/create", name="oro_cms_content_template_create")
     * @Template("@OroCMS/ContentTemplate/update.html.twig")
     * @Acl(
     *      id="oro_cms_content_template_create",
     *      type="entity",
     *      class="OroCMSBundle:ContentTemplate",
     *      permission="CREATE"
     * )
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function createAction(Request $request): array|RedirectResponse
    {
        return $this->update(new ContentTemplate(), $request, true);
    }

    /**
     * @Route("/update/{id}", name="oro_cms_content_template_update", requirements={"id"="\d+"})
     * @Template("@OroCMS/ContentTemplate/update.html.twig")
     * @Acl(
     *      id="oro_cms_content_template_update",
     *      type="entity",
     *      class="OroCMSBundle:ContentTemplate",
     *      permission="EDIT"
     * )
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function updateAction(ContentTemplate $contentTemplate, Request $request): array|RedirectResponse
    {
        return $this->update($contentTemplate, $request);
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function update(
        ContentTemplate $contentTemplate,
        Request $request,
        bool $isCreate = false
    ): array|RedirectResponse {
        $updateHandler = $this->container->get(UpdateHandlerFacade::class);
        $translator    = $this->container->get(TranslatorInterface::class);

        $message = $isCreate ?
            $translator->trans('oro.cms.controller.contenttemplate.saved.message') :
            $translator->trans('oro.cms.controller.contenttemplate.updated.message');

        return $updateHandler->update(
            $contentTemplate,
            $this->createForm(ContentTemplateType::class, $contentTemplate),
            $message,
            $request,
            ContentTemplateHandler::ALIAS
        );
    }

    /**
     * @Route("/content/{id}", name="oro_cms_content_template_content", requirements={"id"="\d+"})
     * @AclAncestor("oro_cms_content_template_view")
     */
    public function getContentAction(ContentTemplate $contentTemplate): JsonResponse
    {
        return new JsonResponse(
            $this->container->get(ContentTemplateContentProvider::class)->getContent($contentTemplate)
        );
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            UpdateHandlerFacade::class,
            TranslatorInterface::class,
            ContentTemplateContentProvider::class
        ]);
    }
}
