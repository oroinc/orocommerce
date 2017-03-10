<?php

namespace Oro\Bundle\ShoppingListBundle\Processor;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroup;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;

class QuickAddCheckoutProcessor extends AbstractShoppingListQuickAddProcessor
{
    const NAME = 'oro_shopping_list_to_checkout_quick_add_processor';

    /** @var ShoppingListManager */
    protected $shoppingListManager;

    /** @var ActionGroupRegistry */
    protected $actionGroupRegistry;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var DateTimeFormatter */
    protected $dateFormatter;

    /** @var string */
    protected $actionGroupName;

    /** @var ActionGroup|null */
    protected $actionGroup = false;

    /**
     * @param ShoppingListManager $shoppingListManager
     * @return QuickAddCheckoutProcessor
     */
    public function setShoppingListManager(ShoppingListManager $shoppingListManager)
    {
        $this->shoppingListManager = $shoppingListManager;

        return $this;
    }

    /**
     * @param ActionGroupRegistry $actionGroupRegistry
     * @return QuickAddCheckoutProcessor
     */
    public function setActionGroupRegistry(ActionGroupRegistry $actionGroupRegistry)
    {
        $this->actionGroupRegistry = $actionGroupRegistry;

        return $this;
    }

    /**
     * @param TranslatorInterface $translator
     * @return QuickAddCheckoutProcessor
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;

        return $this;
    }

    /**
     * @param DateTimeFormatter $dateFormatter
     * @return QuickAddCheckoutProcessor
     */
    public function setDateFormatter(DateTimeFormatter $dateFormatter)
    {
        $this->dateFormatter = $dateFormatter;

        return $this;
    }

    /**
     * @param string $groupName
     * @return QuickAddCheckoutProcessor
     */
    public function setActionGroupName($groupName)
    {
        $this->actionGroupName = $groupName;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isAllowed()
    {
        return parent::isAllowed() && null !== $this->getActionGroup();
    }

    /**
     * {@inheritdoc}
     */
    public function process(array $data, Request $request)
    {
        if (empty($data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY]) ||
            !is_array($data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY])
        ) {
            return null;
        }

        $shoppingList = $this->shoppingListManager->create();
        $shoppingList->setLabel($this->getShoppingListLabel());

        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass(ClassUtils::getClass($shoppingList));
        $em->beginTransaction();
        $em->persist($shoppingList);
        $em->flush($shoppingList);

        /** @var Session $session */
        $session = $request->getSession();
        if ($entitiesCount = $this->fillShoppingList($shoppingList, $data)) {
            $actionData = new ActionData(['shoppingList' => $shoppingList]);
            $errors = new ArrayCollection([]);
            $actionData = $this->getActionGroup()->execute($actionData, $errors);

            if ($redirectUrl = $actionData->getRedirectUrl()) {
                $em->commit();

                return new RedirectResponse($redirectUrl);
            } else {
                $errors = $errors->toArray();
                if (is_array($actionData->offsetGet('errors'))) {
                    $errors = array_merge($errors, $actionData->offsetGet('errors'));
                }

                if (!$errors) {
                    $errors[] = $this->messageGenerator->getFailedMessage();
                }

                foreach ($errors as $error) {
                    $session->getFlashBag()->add('error', $error);
                }

                $em->rollback();

                return false;
            }
        } else {
            $session->getFlashBag()->add('error', $this->messageGenerator->getFailedMessage());
        }

        $em->rollback();

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @return string
     */
    protected function getShoppingListLabel()
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $formatterDate = $this->dateFormatter->format($date);

        return $this->translator->trans(
            'oro.frontend.shoppinglist.quick_order.default_label',
            ['%date%' => $formatterDate]
        );
    }

    /**
     * @return ActionGroup
     */
    protected function getActionGroup()
    {
        if (false === $this->actionGroup) {
            $this->actionGroup = $this->actionGroupRegistry->findByName($this->actionGroupName);
        }

        return $this->actionGroup;
    }
}
