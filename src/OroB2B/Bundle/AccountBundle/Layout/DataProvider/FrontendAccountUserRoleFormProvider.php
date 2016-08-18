<?php

namespace OroB2B\Bundle\AccountBundle\Layout\DataProvider;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;

use Oro\Component\Layout\DataProvider\AbstractFormProvider;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Form\Handler\AccountUserRoleUpdateFrontendHandler;

class FrontendAccountUserRoleFormProvider extends AbstractFormProvider
{
    const ACCOUNT_USER_ROLE_CREATE_ROUTE_NAME = 'orob2b_account_frontend_account_user_role_create';
    const ACCOUNT_USER_ROLE_UPDATE_ROUTE_NAME = 'orob2b_account_frontend_account_user_role_update';

    /** @var AccountUserRoleUpdateFrontendHandler */
    protected $handler;

    /**
     * @param FormFactoryInterface $formFactory
     * @param AccountUserRoleUpdateFrontendHandler $handler
     */
    public function __construct(FormFactoryInterface $formFactory, AccountUserRoleUpdateFrontendHandler $handler)
    {
        parent::__construct($formFactory);

        $this->handler = $handler;
    }

    /**
     * Get form accessor with account user role form
     *
     * @param AccountUserRole $accountUserRole
     *
     * @return FormAccessor
     */
    public function getRoleForm(AccountUserRole $accountUserRole)
    {
        if ($accountUserRole->getId()) {
            return $this->getFormAccessor(
                '',
                self::ACCOUNT_USER_ROLE_UPDATE_ROUTE_NAME,
                $accountUserRole,
                ['id' => $accountUserRole->getId()]
            );
        }

        return $this->getFormAccessor('', self::ACCOUNT_USER_ROLE_CREATE_ROUTE_NAME, $accountUserRole);
    }

    /**
     * {@inheritdoc}
     *
     * @param AccountUserRole $data
     */
    protected function getForm($formName, $data = null, array $options = [])
    {
        $form = $this->handler->createForm($data);

        /* This call needs for set privileges data to form */
        //TODO: refactor handler and set privileges data on form creation
        $this->handler->process($data);

        return $form;
    }
}
