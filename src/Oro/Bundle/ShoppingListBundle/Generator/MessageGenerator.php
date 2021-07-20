<?php

namespace Oro\Bundle\ShoppingListBundle\Generator;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Provider\ShoppingListUrlProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Generates a message displayed when an item has been successfully added to the shopping list.
 */
class MessageGenerator
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var ShoppingListUrlProvider */
    protected $shoppingListUrlProvider;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    public function __construct(
        TranslatorInterface $translator,
        ShoppingListUrlProvider $shoppingListUrlProvider,
        DoctrineHelper $doctrineHelper
    ) {
        $this->translator = $translator;
        $this->shoppingListUrlProvider = $shoppingListUrlProvider;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param null|int $shoppingListId
     * @param int $entitiesCount
     * @param null|string $transChoiceKey
     * @return string
     */
    public function getSuccessMessage($shoppingListId = null, $entitiesCount = 0, $transChoiceKey = null)
    {
        $message = $this->translator->trans(
            $transChoiceKey ?: 'oro.shoppinglist.actions.add_success_message',
            ['%count%' => $entitiesCount]
        );

        if ($shoppingListId && $entitiesCount > 0) {
            $message = sprintf(
                '%s (<a href="%s">%s</a>).',
                $message,
                $this->shoppingListUrlProvider->getFrontendUrl(
                    $this->doctrineHelper->getEntityReference(ShoppingList::class, $shoppingListId)
                ),
                $linkTitle = $this->translator->trans('oro.shoppinglist.actions.view')
            );
        }

        return $message;
    }

    /**
     * @return string
     */
    public function getFailedMessage()
    {
        return $this->translator->trans('oro.shoppinglist.actions.failed_mesage');
    }
}
