<?php

namespace OroB2B\Bundle\ShoppingListBundle\Provider;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAction;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Form\Type\FrontendLineItemType;

class FrontendLineItemForm implements DataProviderInterface
{
    /** @var FormAccessor */
    protected $data;

    /** @var FormInterface */
    protected $form;

    /** @var FormFactoryInterface */
    protected $formFactory;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @param FormFactoryInterface $formFactory
     * @param SecurityFacade $securityFacade
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        SecurityFacade $securityFacade
    ) {
        $this->formFactory = $formFactory;
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return 'orob2b_shopping_list_frontend_line_item_form';
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        if (!$this->data) {
            $this->data = new FormAccessor(
                $this->getForm($context->data()->get('product')),
                FormAction::createByRoute('orob2b_account_frontend_account_user_register')
            );
        }
        return $this->data;
    }

    public function getForm(Product $product)
    {
        if (!$this->form) {
            $this->form = $this->formFactory
                ->create(FrontendLineItemType::NAME, $this->getLineItem($product));
        }
        return $this->form;
    }

    public function getLineItem(Product $product)
    {
        $accountUser = $this->securityFacade->getLoggedUser();
        if (!$accountUser) {
            return null;
        }

        $this->data = (new LineItem())
            ->setProduct($product)
            ->setAccountUser($accountUser)
            ->setOrganization($accountUser->getOrganization());

        return $this->data;
    }
}
