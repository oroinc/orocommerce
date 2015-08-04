<?php

namespace OroB2B\Bundle\ShoppingListBundle\EventListener;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
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
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @param TranslatorInterface   $translator
     * @param TokenStorageInterface $tokenStorage
     * @param FormFactoryInterface  $formFactory
     * @param DoctrineHelper        $doctrineHelper
     */
    public function __construct(
        TranslatorInterface $translator,
        TokenStorageInterface $tokenStorage,
        FormFactoryInterface $formFactory,
        DoctrineHelper $doctrineHelper
    ) {
        $this->translator = $translator;
        $this->tokenStorage = $tokenStorage;
        $this->formFactory = $formFactory;
        $this->doctrineHelper = $doctrineHelper;
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
        $accountUser = $this->getUser();
        if (!$accountUser) {
            return;
        }

        if (!$this->request) {
            return;
        }

        $productId = (int)$this->request->get('id');
        if (!$productId) {
            return;
        }

        /** @var Product $product */
        $product = $this->doctrineHelper->getEntityReference('OroB2BProductBundle:Product', $productId);
        if (!$product) {
            return;
        }

        $lineItem = (new LineItem())
            ->setProduct($product)
            ->setOwner($accountUser)
            ->setOrganization($accountUser->getOrganization());

        /** @var ShoppingListRepository $shoppingListRepository */
        $shoppingListRepository = $this->doctrineHelper->getEntityRepository('OroB2BShoppingListBundle:ShoppingList');
        $currentShoppingList = $shoppingListRepository->findCurrentForAccountUser($accountUser);
        $shoppingLists = $shoppingListRepository->findAllExceptCurrentForAccountUser($accountUser);

        $template = $event->getEnvironment()->render(
            'OroB2BShoppingListBundle:Product/Frontend:view.html.twig',
            [
                'product'             => $product,
                'shoppingLists'       => $shoppingLists,
                'currentShoppingList' => $currentShoppingList,
                'form'                => $this
                    ->formFactory
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
        $token = $this->tokenStorage->getToken();
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
