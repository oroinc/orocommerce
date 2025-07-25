<?php

namespace Oro\Bundle\CMSBundle\Controller;

use Oro\Bundle\CMSBundle\Entity\LoginPage;
use Oro\Bundle\CMSBundle\Form\Type\LoginPageType;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD controller for the LoginPage entity.
 */
class LoginPageController extends AbstractController
{
    #[Route(path: '/', name: 'oro_cms_loginpage_index')]
    #[Template('@OroCMS/LoginPage/index.html.twig')]
    #[AclAncestor('oro_cms_loginpage_view')]
    public function indexAction(): array
    {
        return [
            'entity_class' => LoginPage::class
        ];
    }

    #[Route(path: '/view/{id}', name: 'oro_cms_loginpage_view', requirements: ['id' => '\d+'])]
    #[Template]
    #[Acl(id: 'oro_cms_loginpage_view', type: 'entity', class: LoginPage::class, permission: 'VIEW')]
    public function viewAction(LoginPage $loginPage): array
    {
        return [
            'entity' => $loginPage,
            'loginPageCssField' => $this->getParameter('oro_cms.direct_editing.login_page_css_field')
        ];
    }

    #[Route(path: '/create', name: 'oro_cms_loginpage_create')]
    #[Template('@OroCMS/LoginPage/update.html.twig')]
    #[Acl(id: 'oro_cms_loginpage_create', type: 'entity', class: LoginPage::class, permission: 'CREATE')]
    public function createAction(): array|RedirectResponse
    {
        return $this->update(new LoginPage());
    }

    #[Route(path: '/update/{id}', name: 'oro_cms_loginpage_update', requirements: ['id' => '\d+'])]
    #[Template]
    #[Acl(id: 'oro_cms_loginpage_update', type: 'entity', class: LoginPage::class, permission: 'EDIT')]
    public function updateAction(LoginPage $loginPage): array|RedirectResponse
    {
        return $this->update($loginPage);
    }

    protected function update(LoginPage $loginPage): array|RedirectResponse
    {
        return $this->container->get(UpdateHandlerFacade::class)->update(
            $loginPage,
            $this->createForm(LoginPageType::class, $loginPage),
            $this->container->get(TranslatorInterface::class)->trans('oro.cms.loginpage.save.message')
        );
    }

    #[\Override]
    public static function getSubscribedServices(): array
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
