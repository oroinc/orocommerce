<?php

namespace OroB2B\Bundle\ShoppingListBundle\Layout\DataProvider;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\AbstractServerRenderDataProvider;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Form\Type\FrontendLineItemType;

class FrontendLineItemFormProvider extends AbstractServerRenderDataProvider
{
    /**
     * @var FormAccessor[]
     */
    protected $data = [];

    /**
     * @var FormInterface[]
     */
    protected $form = [];

    /**
     * @var FormFactoryInterface
     */
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
    public function getData(ContextInterface $context)
    {
        $product = $context->data()->get('product');
        if (!isset($this->data[$product->getId()])) {
            $this->data[$product->getId()] = new FormAccessor(
                $this->getForm($product)
            );
        }
        return $this->data[$product->getId()];
    }

    /**
     * @param Product $product
     * @return FormInterface
     */
    public function getForm(Product $product)
    {
        if (!isset($this->form[$product->getId()])) {
            $this->form[$product->getId()] = $this->formFactory
                ->create(FrontendLineItemType::NAME, $this->getLineItem($product));
        }
        return $this->form[$product->getId()];
    }

    /**
     * @param Product $product
     * @return LineItem|null
     */
    public function getLineItem(Product $product)
    {
        $accountUser = $this->securityFacade->getLoggedUser();
        if (!$accountUser) {
            return null;
        }

        $lineItem = (new LineItem())
            ->setProduct($product)
            ->setAccountUser($accountUser)
            ->setOrganization($accountUser->getOrganization());

        return $lineItem;
    }
}
