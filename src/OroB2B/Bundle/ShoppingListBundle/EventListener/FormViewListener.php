<?php

namespace OroB2B\Bundle\ShoppingListBundle\EventListener;

use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use OroB2B\Bundle\ShoppingListBundle\Form\Type\FrontendLineItemType;

class FormViewListener
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var SecurityContextInterface
     */
    protected $securityContext;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @param TranslatorInterface      $translator
     * @param DoctrineHelper           $doctrineHelper
     * @param SecurityContextInterface $securityContext
     */
    public function __construct(
        TranslatorInterface $translator,
        DoctrineHelper $doctrineHelper,
        SecurityContextInterface $securityContext,
        FormFactoryInterface $formFactory
    ) {
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
        $this->securityContext = $securityContext;
        $this->formFactory = $formFactory;
    }

    /**
     * @param Request $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onFrontendProductView(BeforeListRenderEvent $event)
    {
        if (!$this->request) {
            return;
        }

        $accountUser = $this->getUser();
        if (!$accountUser) {
            return;
        }

        $productId = $this->request->get('id');
        /** @var Product $product */
        $product = $this->doctrineHelper->getEntityReference('OroB2BProductBundle:Product', $productId);

        /** @var ShoppingListRepository $shoppingListRepository */
        $shoppingListRepository = $this->doctrineHelper->getEntityRepository('OroB2BShoppingListBundle:ShoppingList');
        $currentShoppingList = $shoppingListRepository->findCurrentForAccountUser($accountUser);
        $shoppingLists = $shoppingListRepository->findAllExceptCurrentForAccountUser($accountUser);

        $lineItem = (new LineItem())
            ->setProduct($product);

        $template = $event->getEnvironment()->render(
            'OroB2BShoppingListBundle:Product:product_view.html.twig',
            [
                'product'             => $product,
                'shoppingLists'       => $shoppingLists,
                'currentShoppingList' => $currentShoppingList,
                'form'                => $this->formFactory
                    ->create(FrontendLineItemType::NAME, $lineItem)
                    ->createView(),
            ]
        );

        $this->addShoppingListBlock($event->getScrollData(), $template);
    }

    /**
     * @return AccountUser|null
     */
    protected function getUser()
    {
        $token = $this->securityContext->getToken();
        if (null === $token) {
            return null;
        }

        return $token->getUser();
    }

    /**
     * @param ScrollData $scrollData
     * @param string     $html
     */
    protected function addShoppingListBlock(ScrollData $scrollData, $html)
    {
        $blockLabel = $this->translator->trans('orob2b.shoppinglist.product.add_to_shopping_list.label');
        $blockId = $scrollData->addBlock($blockLabel);
        $subBlockId = $scrollData->addSubBlock($blockId);
        $scrollData->addSubBlockData($blockId, $subBlockId, $html);
    }
}
