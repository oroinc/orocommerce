<?php

namespace Oro\Bundle\ShoppingListBundle\Processor;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroup;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
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
    private MessageGenerator $messageGenerator;
    private ShoppingListManager $shoppingListManager;
    private ShoppingListLimitManager $shoppingListLimitManager;
    private CurrentShoppingListManager $currentShoppingListManager;
    private ActionGroupRegistry $actionGroupRegistry;
    private TranslatorInterface $translator;
    private DateTimeFormatterInterface $dateFormatter;
    private string $actionGroupName;
    private ActionGroup|null|bool $actionGroup = false;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ShoppingListLineItemHandler $shoppingListLineItemHandler,
        ManagerRegistry $doctrine,
        AclHelper $aclHelper,
        MessageGenerator $messageGenerator,
        ShoppingListManager $shoppingListManager,
        ShoppingListLimitManager $shoppingListLimitManager,
        CurrentShoppingListManager $currentShoppingListManager,
        ActionGroupRegistry $actionGroupRegistry,
        TranslatorInterface $translator,
        DateTimeFormatterInterface $dateFormatter,
        string $actionGroupName
    ) {
        parent::__construct($shoppingListLineItemHandler, $doctrine, $aclHelper);
        $this->messageGenerator = $messageGenerator;
        $this->shoppingListManager = $shoppingListManager;
        $this->shoppingListLimitManager = $shoppingListLimitManager;
        $this->currentShoppingListManager = $currentShoppingListManager;
        $this->actionGroupRegistry = $actionGroupRegistry;
        $this->translator = $translator;
        $this->dateFormatter = $dateFormatter;
        $this->actionGroupName = $actionGroupName;
    }

    /**
     * {@inheritDoc}
     */
    public function isAllowed(): bool
    {
        return parent::isAllowed() && null !== $this->getActionGroup();
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
            $actionData = new ActionData([
                'shoppingList' => $shoppingList,
                'transitionName' => $data[ProductDataStorage::TRANSITION_NAME_KEY] ?? null
            ]);
            $errors = new ArrayCollection([]);
            $actionData = $this->getActionGroup()->execute($actionData, $errors);

            $redirectUrl = $actionData->getRedirectUrl();
            if ($redirectUrl) {
                $em->commit();

                return new RedirectResponse($redirectUrl);
            }

            $errors = $errors->toArray();
            if (\is_array($actionData->offsetGet('errors'))) {
                $errors = array_merge($errors, $actionData->offsetGet('errors'));
            }
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

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'oro_shopping_list_to_checkout_quick_add_processor';
    }

    private function getShoppingListLabel(): string
    {
        return $this->translator->trans(
            'oro.frontend.shoppinglist.quick_order.default_label',
            ['%date%' => $this->dateFormatter->format(new \DateTime('now', new \DateTimeZone('UTC')))]
        );
    }

    private function getActionGroup(): ?ActionGroup
    {
        if (false === $this->actionGroup) {
            $this->actionGroup = $this->actionGroupRegistry->findByName($this->actionGroupName);
        }

        return $this->actionGroup;
    }
}
