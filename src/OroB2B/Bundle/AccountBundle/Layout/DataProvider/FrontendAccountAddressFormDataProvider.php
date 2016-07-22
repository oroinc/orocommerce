<?php

namespace OroB2B\Bundle\AccountBundle\Layout\DataProvider;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAction;

use Oro\Component\Layout\AbstractServerRenderDataProvider;
use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountAddress;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountTypedAddressType;

class FrontendAccountAddressFormDataProvider extends AbstractServerRenderDataProvider
{
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var array|FormAccessorInterface[]
     */
    private $data = [];

    /**
     * @var array|FormInterface[]
     */
    private $forms = [];

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
        /** @var AccountAddress $accountAddress */
        $accountAddress = $context->data()->get('entity');

        $addressId = $accountAddress->getId();

        if (!isset($this->data[$addressId])) {
            $this->data[$addressId] = new FormAccessor(
                $this->getForm($accountAddress),
                $this->getAction($context->data()->get('account'), $addressId)
            );
        }

        return $this->data[$addressId];
    }

    /**
     * @param Account $account
     * @param integer $addressId
     * @return FormAction
     */
    private function getAction(Account $account, $addressId)
    {
        if ($addressId) {
            return FormAction::createByRoute(
                'orob2b_account_frontend_account_address_update',
                ['id' => $addressId, 'entityId' => $account->getId()]
            );
        }

        return FormAction::createByRoute(
            'orob2b_account_frontend_account_address_create',
            ['entityId' => $account->getId()]
        );
    }

    /**
     * @param AccountAddress $accountAddress
     * @return FormInterface
     */
    public function getForm(AccountAddress $accountAddress)
    {
        $addressId = $accountAddress->getId();

        if (!isset($this->forms[$addressId])) {
            $this->forms[$addressId] = $this->formFactory->create(
                AccountTypedAddressType::NAME,
                $accountAddress
            );
        }

        return $this->forms[$addressId];
    }
}
