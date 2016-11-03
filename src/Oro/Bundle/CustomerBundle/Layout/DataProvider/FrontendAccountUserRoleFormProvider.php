<?php

namespace Oro\Bundle\CustomerBundle\Layout\DataProvider;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Oro\Bundle\CustomerBundle\Entity\AccountUserRole;
use Oro\Bundle\CustomerBundle\Form\Handler\AccountUserRoleUpdateFrontendHandler;
use Oro\Bundle\LayoutBundle\Layout\DataProvider\AbstractFormProvider;

class FrontendAccountUserRoleFormProvider extends AbstractFormProvider
{
    const ACCOUNT_USER_ROLE_CREATE_ROUTE_NAME = 'oro_customer_frontend_account_user_role_create';
    const ACCOUNT_USER_ROLE_UPDATE_ROUTE_NAME = 'oro_customer_frontend_account_user_role_update';

    /** @var AccountUserRoleUpdateFrontendHandler */
    protected $handler;

    /**
     * @param FormFactoryInterface                 $formFactory
     * @param AccountUserRoleUpdateFrontendHandler $handler
     * @param UrlGeneratorInterface                $router
     *
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        AccountUserRoleUpdateFrontendHandler $handler,
        UrlGeneratorInterface $router
    ) {
        parent::__construct($formFactory, $router);

        $this->handler = $handler;
    }

    /**
     * Get form accessor with account user role form
     *
     * @param AccountUserRole $accountUserRole
     *
     * @return FormInterface
     */
    public function getRoleFormView(AccountUserRole $accountUserRole)
    {
        if ($accountUserRole->getId()) {
            $options['action'] = $this->generateUrl(
                self::ACCOUNT_USER_ROLE_UPDATE_ROUTE_NAME,
                ['id' => $accountUserRole->getId()]
            );
        } else {
            $options['action'] = $this->generateUrl(
                self::ACCOUNT_USER_ROLE_CREATE_ROUTE_NAME
            );
        }

        return $this->getFormView('', $accountUserRole, $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function createForm($formName, $data = null, array $options = [])
    {
        $form = $this->handler->createForm($data);
        $this->handler->process($data);

        return $form;
    }
}
