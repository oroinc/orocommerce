<?php

namespace OroB2B\Bundle\AccountBundle\Layout\DataProvider;

use Symfony\Component\Form\FormInterface;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAction;

use Oro\Component\Layout\AbstractServerRenderDataProvider;
use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Form\Handler\AccountUserRoleUpdateFrontendHandler;

class FrontendAccountUserRoleFormDataProvider
{
    /** @var AccountUserRoleUpdateFrontendHandler */
    protected $handler;

    /** @var FormAccessor[] */
    protected $data = [];

    /** @var FormInterface[] */
    protected $forms = [];

    /**
     * @param AccountUserRoleUpdateFrontendHandler $handler
     */
    public function __construct(AccountUserRoleUpdateFrontendHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        $role = $context->data()->get('entity');
        $roleId = $role->getId();

        if (!isset($this->data[$roleId])) {
            if ($roleId) {
                $action = FormAction::createByRoute(
                    'orob2b_account_frontend_account_user_role_update',
                    ['id' => $roleId]
                );
            } else {
                $action = FormAction::createByRoute('orob2b_account_frontend_account_user_role_create');
            }

            $this->data[$roleId] = new FormAccessor($this->getForm($role), $action);
        }

        return $this->data[$roleId];
    }

    /**
     * @param AccountUserRole $role
     * @return FormInterface
     */
    public function getForm(AccountUserRole $role)
    {
        $roleId = $role->getId();

        if (!isset($this->forms[$roleId])) {
            $this->forms[$roleId] = $this->handler->createForm($role);

            /* This call needs for set privileges data to form */
            //TODO: refactor handler and set privileges data on form creation
            $this->handler->process($role);
        }

        return $this->forms[$roleId];
    }
}
