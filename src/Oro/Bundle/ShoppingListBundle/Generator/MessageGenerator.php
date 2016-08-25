<?php

namespace Oro\Bundle\ShoppingListBundle\Generator;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

class MessageGenerator
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var UrlGeneratorInterface */
    protected $router;

    /**
     * @param TranslatorInterface $translator
     * @param UrlGeneratorInterface $router
     */
    public function __construct(TranslatorInterface $translator, UrlGeneratorInterface $router)
    {
        $this->translator = $translator;
        $this->router = $router;
    }

    /**
     * @param int $shoppingListId
     * @param int $entitiesCount
     * @param null|string $transChoiceKey
     * @return string
     */
    public function getSuccessMessage($shoppingListId = null, $entitiesCount = 0, $transChoiceKey = null)
    {
        $message = $this->translator->transChoice(
            $transChoiceKey ?: 'oro.shoppinglist.actions.add_success_message',
            $entitiesCount,
            ['%count%' => $entitiesCount]
        );

        if ($shoppingListId && $entitiesCount > 0) {
            $message = sprintf(
                '%s (<a href="%s">%s</a>).',
                $message,
                $this->router->generate('orob2b_shopping_list_frontend_view', ['id' => $shoppingListId]),
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
