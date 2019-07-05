<?php

namespace Oro\Bundle\ShoppingListBundle\Controller\Frontend;

use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Base methods for line item controllers
 */
abstract class AbstractLineItemController extends AbstractController
{
    /**
     * @param ShoppingList $shoppingList
     * @param string $translationKey
     * @return string
     */
    protected function getSuccessMessage(ShoppingList $shoppingList, $translationKey): string
    {
        $link = $this->get(RouterInterface::class)->generate('oro_shopping_list_frontend_view', [
            'id' => $this->get(ShoppingListLimitManager::class)->isOnlyOneEnabled()
                ? null
                : $shoppingList->getId(),
        ]);

        $label = htmlspecialchars($shoppingList->getLabel());

        return $this->get(TranslatorInterface::class)->trans(
            $translationKey,
            ['%shoppinglist%' => sprintf('<a href="%s">%s</a>', $link, $label)]
        );
    }

    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            ShoppingListLimitManager::class,
            RouterInterface::class,
            TranslatorInterface::class,
        ]);
    }
}
