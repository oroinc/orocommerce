<?php

namespace Oro\Bundle\CMSBundle\Controller;

use Oro\Bundle\CMSBundle\Entity\LoginPage;
use Oro\Bundle\CMSBundle\Form\Type\LoginPageType;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * CRUD controller for the LoginPage entity.
 */
class LoginPageController extends AbstractController
{
    /**
     * @Route("/", name="oro_cms_loginpage_index")
     * @Template("OroCMSBundle:LoginPage:index.html.twig")
     * @AclAncestor("oro_cms_loginpage_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => LoginPage::class
        ];
    }

    /**
     * @Route("/view/{id}", name="oro_cms_loginpage_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_cms_loginpage_view",
     *      type="entity",
     *      class="OroCMSBundle:LoginPage",
     *      permission="VIEW"
     * )
     *
     * @param LoginPage $loginPage
     * @return array
     */
    public function viewAction(LoginPage $loginPage)
    {
        return [
            'entity' => $loginPage,
            'loginPageCssField' => $this->container->getParameter('oro_cms.direct_editing.login_page_css_field')
        ];
    }

    /**
     * @Route("/create", name="oro_cms_loginpage_create")
     * @Template("OroCMSBundle:LoginPage:update.html.twig")
     * @Acl(
     *      id="oro_cms_loginpage_create",
     *      type="entity",
     *      class="OroCMSBundle:LoginPage",
     *      permission="CREATE"
     * )
     *
     * @return array
     */
    public function createAction()
    {
        return $this->update(new LoginPage());
    }

    /**
     * @Route("/update/{id}", name="oro_cms_loginpage_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_cms_loginpage_update",
     *      type="entity",
     *      class="OroCMSBundle:LoginPage",
     *      permission="EDIT"
     * )
     *
     * @param LoginPage $loginPage
     * @return array
     */
    public function updateAction(LoginPage $loginPage)
    {
        return $this->update($loginPage);
    }

    /**
     * @param LoginPage $loginPage
     * @return array|RedirectResponse
     */
    protected function update(LoginPage $loginPage)
    {
        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $loginPage,
            $this->createForm(LoginPageType::class, $loginPage),
            function (LoginPage $loginPage) {
                return [
                    'route' => 'oro_cms_loginpage_update',
                    'parameters' => ['id' => $loginPage->getId()]
                ];
            },
            function (LoginPage $loginPage) {
                return [
                    'route' => 'oro_cms_loginpage_view',
                    'parameters' => ['id' => $loginPage->getId()]
                ];
            },
            $this->get('translator')->trans('oro.cms.loginpage.save.message')
        );
    }
}
