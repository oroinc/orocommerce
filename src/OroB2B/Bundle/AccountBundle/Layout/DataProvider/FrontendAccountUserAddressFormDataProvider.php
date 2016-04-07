<?php

namespace OroB2B\Bundle\AccountBundle\Layout\DataProvider;

use Symfony\Component\Form\FormFactory;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Component\Layout\AbstractServerRenderDataProvider;
use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserTypedAddressType;

class FrontendAccountUserAddressFormDataProvider extends AbstractServerRenderDataProvider
{
    /**
     * @var FormFactory
     */
    private $formFactory;

    /**
     * @var FormAccessor
     */
    private $data;

    /**
     * @var AccountUserTypedAddressType
     */
    private $type;

    /**
     * @param FormFactory $formFactory
     * @param AccountUserTypedAddressType $type
     */
    public function __construct(FormFactory $formFactory, AccountUserTypedAddressType $type)
    {
        $this->formFactory = $formFactory;
        $this->type = $type;
    }

    /** {@inheritdoc} */
    public function getData(ContextInterface $context)
    {
        if (null === $this->data) {
            $this->data = new FormAccessor(
                $this->createForm($context->data()->get('entity'))
            );
        }

        return $this->data;
    }

    /**
     * @param AccountUserAddress $address
     * @return \Symfony\Component\Form\Form|\Symfony\Component\Form\FormInterface
     */
    protected function createForm(AccountUserAddress $address = null)
    {
        return $this->formFactory->create($this->type, $address ?: new AccountUserAddress());
    }
}
