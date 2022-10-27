<?php

namespace Oro\Bundle\ShoppingListBundle\Controller\Frontend;

use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Provider\ShoppingListUrlProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Base methods for line item controllers
 */
abstract class AbstractLineItemController extends AbstractController
{
    protected function getSuccessMessage(ShoppingList $shoppingList, string $translationKey): string
    {
        $link = $this->get(ShoppingListUrlProvider::class)->getFrontendUrl($shoppingList);
        $label = htmlspecialchars($shoppingList->getLabel());

        return $this->get(TranslatorInterface::class)->trans(
            $translationKey,
            ['%shoppinglist%' => sprintf('<a href="%s">%s</a>', $link, $label)]
        );
    }

    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            ShoppingListUrlProvider::class,
            RouterInterface::class,
            TranslatorInterface::class,
        ]);
    }
}
