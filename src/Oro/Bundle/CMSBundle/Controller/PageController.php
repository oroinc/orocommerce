<?php

namespace Oro\Bundle\CMSBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Form\Handler\PageHandler;
use Oro\Bundle\CMSBundle\Form\Type\PageType;

class PageController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_cms_page_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_cms_page_view",
     *      type="entity",
     *      class="OroCMSBundle:Page",
     *      permission="VIEW"
     * )
     *
     * @param Page $page
     * @return array
     */
    public function viewAction(Page $page)
    {
        return [
            'entity' => $page
        ];
    }

    /**
     * @Route("/info/{id}", name="orob2b_cms_page_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("orob2b_cms_page_view")
     *
     * @param Page $page
     * @return array
     */
    public function infoAction(Page $page)
    {
        return [
            'entity' => $page
        ];
    }

    /**
     * @Route("/", name="orob2b_cms_page_index")
     * @Template
     * @AclAncestor("orob2b_cms_page_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('oro_cms.entity.page.class')
        ];
    }

    /**
     * @Route("/create/{id}", name="orob2b_cms_page_create", requirements={"id"="\d+"}, defaults={"id"=null})
     * @Template("OroCMSBundle:Page:update.html.twig")
     * @Acl(
     *      id="orob2b_cms_page_create",
     *      type="entity",
     *      class="OroCMSBundle:Page",
     *      permission="CREATE"
     * )
     *
     * @param int|null $id
     * @return array|RedirectResponse
     */
    public function createAction($id)
    {
        $page = new Page();
        if ($id) {
            $parentPage = $this->getDoctrine()->getRepository('OroCMSBundle:Page')->find($id);
            if (!$parentPage) {
                throw new \LogicException(sprintf('Page with identifier %s does not exist', $id));
            }
            $page->setParentPage($parentPage);
        }

        return $this->update($page);
    }

    /**
     * @Route("/update/{id}", name="orob2b_cms_page_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_cms_page_update",
     *      type="entity",
     *      class="OroCMSBundle:Page",
     *      permission="EDIT"
     * )
     * @param Page $page
     * @return array|RedirectResponse
     */
    public function updateAction(Page $page)
    {
        return $this->update($page);
    }

    /**
     * @param Page $page
     * @return array|RedirectResponse
     */
    protected function update(Page $page)
    {
        $form = $this->createForm(PageType::NAME);
        $handler = new PageHandler(
            $form,
            $this->getRequest(),
            $this->getDoctrine()->getManagerForClass('OroCMSBundle:Page'),
            $this->get('oro_redirect.slug.manager')
        );

        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $page,
            $form,
            function (Page $page) {
                return [
                    'route' => 'orob2b_cms_page_update',
                    'parameters' => ['id' => $page->getId()]
                ];
            },
            function (Page $page) {
                return [
                    'route' => 'orob2b_cms_page_view',
                    'parameters' => ['id' => $page->getId()]
                ];
            },
            $this->get('translator')->trans('oro.cms.controller.page.saved.message'),
            $handler
        );
    }
}
