<?php

namespace OroB2B\Bundle\ShoppingListBundle\Processor;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionManager;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;

use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;

class QuickAddCheckoutProcessor extends AbstractShoppingListQuickAddProcessor
{
    const NAME = 'orob2b_shopping_list_to_checkout_quick_add_processor';

    /**
     * @var ShoppingListManager
     */
    protected $shoppingListManager;

    /**
     * @var ActionManager
     */
    protected $actionManager;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var DateTimeFormatter
     */
    protected $dateFormatter;

    /**
     * @var string
     */
    protected $actionName;

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
     * @param ActionManager $actionManager
     * @return QuickAddCheckoutProcessor
     */
    public function setActionManager(ActionManager $actionManager)
    {
        $this->actionManager = $actionManager;

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
     * @param string $actionName
     * @return QuickAddCheckoutProcessor
     */
    public function setActionName($actionName)
    {
        $this->actionName = $actionName;

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
            $actionData = new ActionData(['data' => $shoppingList]);
            $errors = new ArrayCollection([]);
            $actionData = $this->actionManager->execute($this->actionName, $actionData, $errors);

            if ($redirectUrl = $actionData->getRedirectUrl()) {
                $em->commit();

                return new RedirectResponse($redirectUrl);
            } else {
                if (!$errors->count()) {
                    $errors->add($this->messageGenerator->getFailedMessage());
                }

                foreach ($errors as $error) {
                    $session->getFlashBag()->add('error', $error);
                }
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
            'orob2b.frontend.shoppinglist.quick_order.default_label',
            ['%date%' => $formatterDate]
        );
    }
}
