<?php

namespace OroB2B\Bundle\ShoppingListBundle\DataGrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;
use OroB2B\Bundle\ShoppingListBundle\DataGrid\Extension\MassAction\AddProductsMassActionArgsParser as ArgsParser;
use OroB2B\Bundle\ShoppingListBundle\Handler\ShoppingListLineItemHandler;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Translation\TranslatorInterface;

class AddProductsMassActionHandler implements MassActionHandlerInterface
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var RouterInterface */
    protected $router;

    /**  @var ShoppingListLineItemHandler */
    protected $shoppingListLineItemHandler;

    /**
     * @param ShoppingListLineItemHandler $shoppingListLineItemHandler
     * @param TranslatorInterface $translator
     * @param RouterInterface $router
     */
    public function __construct(
        ShoppingListLineItemHandler $shoppingListLineItemHandler,
        TranslatorInterface $translator,
        RouterInterface $router
    ) {
        $this->shoppingListLineItemHandler = $shoppingListLineItemHandler;
        $this->translator = $translator;
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(MassActionHandlerArgs $args)
    {
        $argsParser = new ArgsParser($args);
        $shoppingList = $this->shoppingListLineItemHandler->getShoppingList($argsParser->getShoppingListId());
        if (!$shoppingList) {
            return $this->generateResponse($args);
        }

        try {
            $addedCnt = $this->shoppingListLineItemHandler->createForShoppingList(
                $shoppingList,
                $argsParser->getProductIds()
            );

            return $this->generateResponse($args, $addedCnt, $shoppingList->getId());
        } catch (AccessDeniedException $e) {
            return $this->generateResponse($args);
        }
    }

    /**
     * @param MassActionHandlerArgs $args
     * @param int $entitiesCount
     * @param int|null $shoppingListId
     *
     * @return MassActionResponse
     */
    protected function generateResponse(MassActionHandlerArgs $args, $entitiesCount = 0, $shoppingListId = null)
    {
        $message = $this->translator->transChoice(
            $args->getMassAction()->getOptions()->offsetGetByPath(
                '[messages][success]',
                'orob2b.shoppinglist.actions.add_success_message'
            ),
            $entitiesCount,
            ['%count%' => $entitiesCount]
        );

        if ($shoppingListId && $entitiesCount > 0) {
            $link = $this->router->generate('orob2b_shopping_list_frontend_view', ['id' => $shoppingListId]);
            $linkTitle = $this->translator->trans('orob2b.shoppinglist.actions.view');
            $message = sprintf("%s (<a href='%s'>%s</a>).", $message, $link, $linkTitle);
        }

        return new MassActionResponse(
            $entitiesCount > 0,
            $message,
            ['count' => $entitiesCount]
        );
    }
}
