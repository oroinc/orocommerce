<?php

namespace OroB2B\Bundle\AccountBundle\Layout\DataProvider;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Test\FormInterface;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAction;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\AbstractServerRenderDataProvider;

use OroB2B\Bundle\AccountBundle\Form\Type\FrontendAccountUserProfileType;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

class FrontendAccountUserProfileFormProvider
{
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
                    'orob2b_account_frontend_account_user_profile_update',
                    ['id' => $accountUserId]
                );

                $this->data[$accountUserId] = new FormAccessor(
                    $this->getForm($accountUser),
                    $action
                );
            }
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
        $accountUserId = $accountUser->getId();
        if (!isset($this->form[$accountUserId])) {
            $this->form[$accountUserId] = $this->formFactory
                ->create(FrontendAccountUserProfileType::NAME, $accountUser);
        }

        return $this->form[$accountUserId];
    }
}
