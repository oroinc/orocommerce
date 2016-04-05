<?php

namespace OroB2B\Bundle\AccountBundle\Layout\DataProvider;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAction;

use Oro\Component\Layout\AbstractServerRenderDataProvider;
use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\AccountBundle\Form\Handler\AccountUserRoleUpdateFrontendHandler;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Form\Type\FrontendAccountUserType;

class FrontendAccountUserFormDataProvider extends AbstractServerRenderDataProvider
{
    /** @var AccountUserRoleUpdateFrontendHandler */
    protected $handler;

    /** @var FormAccessor[] */
    protected $data = [];

    /** @var FormInterface[] */
    protected $form = [];

    /** @var FormFactoryInterface */
    protected $formFactory;

    /**
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        $accountUser = $context->data()->get('entity');
        $accountUserId = $accountUser->getId();

        if (!isset($this->data[$accountUserId])) {
            if ($accountUserId) {
                $action = FormAction::createByRoute(
                    'orob2b_account_frontend_account_user_update',
                    ['id' => $accountUserId]
                );
            } else {
                $action = FormAction::createByRoute('orob2b_account_frontend_account_user_create');
            }

            $this->data[$accountUserId] = new FormAccessor($this->getForm($accountUser), $action);
        }

        return $this->data[$accountUserId];
    }

    /**
     * @param AccountUser $accountUser
     *
     * @return FormInterface
     */
    public function getForm(AccountUser $accountUser)
    {
        $requestId = $accountUser->getId();
        if (!isset($this->form[$requestId])) {
            $this->form[$requestId] = $this->formFactory
                ->create(FrontendAccountUserType::NAME, $accountUser);
        }
        return $this->form[$requestId];
    }
}
