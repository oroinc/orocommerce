<?php

namespace OroB2B\Bundle\WebsiteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Form\Type\WebsiteType;

class WebsiteController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_website_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_website_view",
     *      type="entity",
     *      class="OroB2BWebsiteBundle:Website",
     *      permission="VIEW"
     * )
     *
     * @param Website $website
     * @return array
     */
    public function viewAction(Website $website)
    {
        return [
            'entity' => $website,
        ];
    }

    /**
     * @Route("/info/{id}", name="orob2b_website_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("orob2b_website_view")
     *
     * @param Website $website
     *
     * @return array
     */
    public function infoAction(Website $website)
    {
        return [
            'website' => $website,
        ];
    }

    /**
     * @Route("/", name="orob2b_website_index")
     * @Template
     * @AclAncestor("orob2b_website_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_website.entity.website.class')
        ];
    }

    /**
     * Create website
     *
     * @Route("/create", name="orob2b_website_create")
     * @Template("OroB2BWebsiteBundle:Website:update.html.twig")
     * @Acl(
     *      id="orob2b_website_create",
     *      type="entity",
     *      class="OroB2BWebsiteBundle:Website",
     *      permission="CREATE"
     * )
     * @return array|RedirectResponse
     */
    public function createAction()
    {
        return $this->update(new Website());
    }

    /**
     * Edit website form
     *
     * @Route("/update/{id}", name="orob2b_website_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_website_update",
     *      type="entity",
     *      class="OroB2BWebsiteBundle:Website",
     *      permission="EDIT"
     * )
     *
     * @param Website $website
     * @return array|RedirectResponse
     */
    public function updateAction(Website $website)
    {
        return $this->update($website);
    }

    /**
     * @param Website $website
     *
     * @return array|RedirectResponse
     */
    protected function update(Website $website)
    {
        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $website,
            $this->createForm(WebsiteType::NAME, $website),
            function (Website $website) {
                return [
                    'route' => 'orob2b_website_update',
                    'parameters' => ['id' => $website->getId()]
                ];
            },
            function (Website $website) {
                return [
                    'route' => 'orob2b_website_view',
                    'parameters' => ['id' => $website->getId()]
                ];
            },
            $this->get('translator')->trans('orob2b.website.controller.website.saved.message')
        );
    }
}
