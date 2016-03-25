<?php

namespace OroB2B\Bundle\ShoppingListBundle\Processor;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;
use OroB2B\Bundle\ShoppingListBundle\Generator\MessageGenerator;

class QuickAddProcessor extends AbstractShoppingListQuickAddProcessor
{
    const NAME = 'orob2b_shopping_list_quick_add_processor';

    /**
     * @var MessageGenerator
     */
    protected $messageGenerator;

    /**
     * @param MessageGenerator $messageGenerator
     * @return QuickAddProcessor
     */
    public function setMessageGenerator(MessageGenerator $messageGenerator)
    {
        $this->messageGenerator = $messageGenerator;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function process(array $data, Request $request)
    {
        if (empty($data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY]) ||
            !is_array($data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY])
        ) {
            return;
        }

        $shoppingListId = null;
        if (!empty($data[ProductDataStorage::ADDITIONAL_DATA_KEY])) {
            $shoppingListId = (int)$data[ProductDataStorage::ADDITIONAL_DATA_KEY] ? : null;
        }
        $shoppingList = $this->shoppingListLineItemHandler->getShoppingList($shoppingListId);

        /** @var Session $session */
        $session = $request->getSession();
        $flashBag = $session->getFlashBag();

        if ($entitiesCount = $this->fillShoppingList($shoppingList, $data)) {
            $flashBag->add(
                'success',
                $this->messageGenerator->getSuccessMessage($shoppingList->getId(), $entitiesCount)
            );
        } else {
            $flashBag->add('error', $this->messageGenerator->getFailedMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
