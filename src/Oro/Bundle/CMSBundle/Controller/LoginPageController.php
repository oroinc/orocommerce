<?php

namespace Oro\Bundle\CMSBundle\Controller;

use Oro\Bundle\CMSBundle\Entity\LoginPage;
use Oro\Bundle\CMSBundle\Form\Type\LoginPageType;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD controller for the LoginPage entity.
 */
class LoginPageController extends AbstractController
{
    /**
     * @Route("/", name="oro_cms_loginpage_index")
     * @Template("@OroCMS/LoginPage/index.html.twig")
     * @AclAncestor("oro_cms_loginpage_view")
     */
    public function indexAction(): array
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
     */
    public function viewAction(LoginPage $loginPage): array
    {
        return [
            'entity' => $loginPage,
            'loginPageCssField' => $this->getParameter('oro_cms.direct_editing.login_page_css_field')
        ];
    }

    /**
     * @Route("/create", name="oro_cms_loginpage_create")
     * @Template("@OroCMS/LoginPage/update.html.twig")
     * @Acl(
     *      id="oro_cms_loginpage_create",
     *      type="entity",
     *      class="OroCMSBundle:LoginPage",
     *      permission="CREATE"
     * )
     */
    public function createAction(): array|RedirectResponse
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
     */
    public function updateAction(LoginPage $loginPage): array|RedirectResponse
    {
        return $this->update($loginPage);
    }

    protected function update(LoginPage $loginPage): array|RedirectResponse
    {
        return $this->get(UpdateHandlerFacade::class)->update(
            $loginPage,
            $this->createForm(LoginPageType::class, $loginPage),
            $this->get(TranslatorInterface::class)->trans('oro.cms.loginpage.save.message')
        );
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                UpdateHandlerFacade::class
            ]
        );
    }
}
