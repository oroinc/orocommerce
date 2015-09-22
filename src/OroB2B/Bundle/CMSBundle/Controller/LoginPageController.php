<?php

namespace OroB2B\Bundle\CMSBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\CMSBundle\Entity\LoginPage;
use OroB2B\Bundle\CMSBundle\Form\Type\LoginPageType;

class LoginPageController extends Controller
{
    /**
     * @Route("/", name="orob2b_cms_loginpage_index")
     * @Template("OroB2BCMSBundle:LoginPage:index.html.twig")
     * @AclAncestor("orob2b_cms_loginpage_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_cms.loginpage.class')
        ];
    }

    /**
     * @Route("/view/{id}", name="orob2b_cms_loginpage_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_cms_loginpage_view",
     *      type="entity",
     *      class="OroB2BCMSBundle:LoginPage",
     *      permission="VIEW"
     * )
     *
     * @param LoginPage $loginPage
     * @return array
     */
    public function viewAction(LoginPage $loginPage)
    {
        return [
            'entity' => $loginPage
        ];
    }

    /**
     * @Route("/create", name="orob2b_cms_loginpage_create")
     * @Template("OroB2BCMSBundle:LoginPage:update.html.twig")
     * @Acl(
     *      id="orob2b_cms_loginpage_create",
     *      type="entity",
     *      class="OroB2BCMSBundle:LoginPage",
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
     * @Route("/update/{id}", name="orob2b_cms_loginpage_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_cms_loginpage_update",
     *      type="entity",
     *      class="OroB2BCMSBundle:LoginPage",
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
            $this->createForm(LoginPageType::NAME, $loginPage),
            function (LoginPage $loginPage) {
                return [
                    'route' => 'orob2b_cms_loginpage_update',
                    'parameters' => ['id' => $loginPage->getId()]
                ];
            },
            function (LoginPage $loginPage) {
                return [
                    'route' => 'orob2b_cms_loginpage_view',
                    'parameters' => ['id' => $loginPage->getId()]
                ];
            },
            $this->get('translator')->trans('orob2b.cms.loginpage.save.message')
        );
    }
}
