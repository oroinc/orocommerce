<?php

namespace Oro\Bundle\ShoppingListBundle\Form\Handler;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Event\EventDispatcher;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\FormBundle\Event\FormHandler\Events;
use Oro\Bundle\FormBundle\Event\FormHandler\FormProcessEvent;
use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\MatrixGridOrderManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollection;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * The handler for the matrix grid order form.
 */
class MatrixGridOrderFormHandler implements FormHandlerInterface
{
    use RequestHandlerTrait;

    /** @var EventDispatcher */
    private $eventDispatcher;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var MatrixGridOrderManager */
    private $matrixGridOrderManager;

    /** @var ShoppingListManager */
    private $shoppingListManager;

    public function __construct(
        EventDispatcher $eventDispatcher,
        DoctrineHelper $doctrineHelper,
        MatrixGridOrderManager $matrixGridOrderManager,
        ShoppingListManager $shoppingListManager
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->doctrineHelper = $doctrineHelper;
        $this->matrixGridOrderManager = $matrixGridOrderManager;
        $this->shoppingListManager = $shoppingListManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process($data, FormInterface $form, Request $request): bool
    {
        if (!$data instanceof MatrixCollection) {
            throw new \InvalidArgumentException(
                sprintf('The "data" argument should be instance of the "%s" entity', MatrixCollection::class)
            );
        }

        $event = new FormProcessEvent($form, $data);
        $this->eventDispatcher->dispatch($event, Events::BEFORE_FORM_DATA_SET);

        if ($event->isFormProcessInterrupted()) {
            return false;
        }

        $form->setData($data);

        if ($request->getMethod() === Request::METHOD_POST) {
            $event = new FormProcessEvent($form, $data);
            $this->eventDispatcher->dispatch($event, Events::BEFORE_FORM_SUBMIT);

            if ($event->isFormProcessInterrupted()) {
                return false;
            }

            $this->submitPostPutRequest($form, $request);

            if ($form->isValid()) {
                $manager = $this->doctrineHelper->getEntityManager(LineItem::class);
                $manager->beginTransaction();

                try {
                    $this->saveData($data, $form, $request);
                    $manager->commit();
                } catch (\Exception $exception) {
                    $manager->rollback();
                    return false;
                }

                return true;
            }
        }

        return false;
    }

    protected function saveData(MatrixCollection $data, FormInterface $form, Request $request): void
    {
        $shoppingList = $request->attributes->get('shoppingList');
        if (!$shoppingList instanceof ShoppingList) {
            throw new \InvalidArgumentException('The "shoppingList" request argument should be present');
        }

        $product = $request->attributes->get('product');
        if (!$product instanceof Product) {
            throw new \InvalidArgumentException('The "product" request argument should be present');
        }

        $this->eventDispatcher->dispatch(new AfterFormProcessEvent($form, $data), Events::BEFORE_FLUSH);

        $lineItems = $this->matrixGridOrderManager->convertMatrixIntoLineItems(
            $form->getData(),
            $product,
            $request->request->get('matrix_collection', [])
        );

        foreach ($lineItems as $lineItem) {
            $this->shoppingListManager->updateLineItem($lineItem, $shoppingList);
        }

        $this->matrixGridOrderManager->addEmptyMatrixIfAllowed($shoppingList, $product, $lineItems);

        $this->eventDispatcher->dispatch(new AfterFormProcessEvent($form, $data), Events::AFTER_FLUSH);
    }
}
