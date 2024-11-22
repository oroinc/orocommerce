<?php

namespace Oro\Bundle\ShoppingListBundle\Processor;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\StartQuickOrderCheckoutInterface;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\ProductBundle\Model\Mapping\ProductMapperInterface;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Generator\MessageGenerator;
use Oro\Bundle\ShoppingListBundle\Handler\ShoppingListLineItemHandler;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Handles logic related to quick order process.
 */
class QuickAddCheckoutProcessor extends AbstractShoppingListQuickAddProcessor
{
    private ManagerRegistry $doctrine;
    private MessageGenerator $messageGenerator;
    private ShoppingListManager $shoppingListManager;
    private ShoppingListLimitManager $shoppingListLimitManager;
    private CurrentShoppingListManager $currentShoppingListManager;
    private TranslatorInterface $translator;
    private DateTimeFormatterInterface $dateFormatter;
    private StartQuickOrderCheckoutInterface $startQuickOrderCheckout;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ShoppingListLineItemHandler $shoppingListLineItemHandler,
        ProductMapperInterface $productMapper,
        ManagerRegistry $doctrine,
        MessageGenerator $messageGenerator,
        ShoppingListManager $shoppingListManager,
        ShoppingListLimitManager $shoppingListLimitManager,
        CurrentShoppingListManager $currentShoppingListManager,
        ActionGroupRegistry $actionGroupRegistry,
        TranslatorInterface $translator,
        DateTimeFormatterInterface $dateFormatter,
        string $actionGroupName
    ) {
        parent::__construct($shoppingListLineItemHandler, $productMapper);

        $this->doctrine = $doctrine;
        $this->messageGenerator = $messageGenerator;
        $this->shoppingListManager = $shoppingListManager;
        $this->shoppingListLimitManager = $shoppingListLimitManager;
        $this->currentShoppingListManager = $currentShoppingListManager;
        $this->translator = $translator;
        $this->dateFormatter = $dateFormatter;
    }

    public function isAllowed(): bool
    {
        return parent::isAllowed();
    }

    public function setStartQuickOrderCheckout(StartQuickOrderCheckoutInterface $startQuickOrderCheckout): void
    {
        $this->startQuickOrderCheckout = $startQuickOrderCheckout;
    }

    /**
     * {@inheritDoc}
     */
    public function process(array $data, Request $request): ?Response
    {
        if (empty($data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY]) ||
            !\is_array($data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY])
        ) {
            return null;
        }

        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(ShoppingList::class);
        $em->beginTransaction();

        if ($this->shoppingListLimitManager->isReachedLimit()) {
            $shoppingList = $this->shoppingListManager->edit(
                $this->currentShoppingListManager->getCurrent(true),
                $this->getShoppingListLabel()
            );
            $this->shoppingListManager->removeLineItems($shoppingList);
        } else {
            $shoppingList = $this->shoppingListManager->create(false, $this->getShoppingListLabel());
            $em->persist($shoppingList);
            $em->flush($shoppingList);
        }

        /** @var Session $session */
        $session = $request->getSession();
        if ($this->fillShoppingList($shoppingList, $data)) {
            $startResult = $this->startQuickOrderCheckout->execute(
                $shoppingList,
                $data[ProductDataStorage::TRANSITION_NAME_KEY] ?? null
            );

            $redirectUrl = $startResult['redirectUrl'] ?? null;
            if ($redirectUrl) {
                $em->commit();

                return new RedirectResponse($redirectUrl);
            }

            $errors = $startResult['errors'] ?? [];
            if (!$errors) {
                $errors[] = $this->messageGenerator->getFailedMessage();
            }
            foreach ($errors as $error) {
                $session->getFlashBag()->add('error', $error);
            }

            $em->rollback();

            return null;
        }

        $session->getFlashBag()->add('error', $this->messageGenerator->getFailedMessage());

        $em->rollback();

        return null;
    }

    private function getShoppingListLabel(): string
    {
        return $this->translator->trans(
            'oro.frontend.shoppinglist.quick_order.default_label',
            ['%date%' => $this->dateFormatter->format(new \DateTime('now', new \DateTimeZone('UTC')))]
        );
    }
}
