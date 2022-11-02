<?php

namespace Oro\Bundle\ShoppingListBundle\Handler;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Model\LineItemModel;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * The handler for the line item batch update.
 */
class ShoppingListLineItemBatchUpdateHandler
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ShoppingListManager */
    private $shoppingListManager;

    /** @var ValidatorInterface */
    private $validator;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        ShoppingListManager $shoppingListManager,
        ValidatorInterface $validator
    ) {
        $this->shoppingListManager = $shoppingListManager;
        $this->doctrineHelper = $doctrineHelper;
        $this->validator = $validator;
    }

    /**
     * @param LineItemModel[] $collection
     * @param ShoppingList $shoppingList
     * @return array
     */
    public function process(array $collection, ShoppingList $shoppingList): array
    {
        $lineItems = $this->getLineItems(
            array_map(
                static function (LineItemModel $model) {
                    return $model->getId();
                },
                $collection
            )
        );

        $products = [];
        $unitCodes = [];

        foreach ($collection as $lineItemModel) {
            if (isset($lineItems[$lineItemModel->getId()])) {
                $lineItem = $lineItems[$lineItemModel->getId()];

                $products[] = $lineItem->getProduct();
                $unitCodes[] = $lineItemModel->getUnitCode();
            }
        }

        /** @var ProductUnitRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository(ProductUnit::class);
        $productUnits = $repository->getProductsUnitsByCodes($products, $unitCodes);

        foreach ($collection as $lineItemModel) {
            if (isset($lineItems[$lineItemModel->getId()], $productUnits[$lineItemModel->getUnitCode()])) {
                $lineItem = $lineItems[$lineItemModel->getId()];
                $lineItem->setQuantity($lineItemModel->getQuantity());
                $lineItem->setUnit($productUnits[$lineItemModel->getUnitCode()]);

                $this->shoppingListManager->addLineItem($lineItem, $shoppingList, false, true);
            }
        }

        $errors = $this->getShoppingListErrors($shoppingList);
        if (!$errors) {
            $manager = $this->doctrineHelper->getEntityManagerForClass(ShoppingList::class);
            $manager->flush();
        }

        return $errors;
    }

    /**
     * @param array $ids
     * @return LineItem[]
     */
    private function getLineItems(array $ids): array
    {
        $repository = $this->doctrineHelper->getEntityRepository(LineItem::class);

        $data = [];
        foreach ($repository->findBy(['id' => $ids]) as $item) {
            $data[$item->getId()] = $item;
        }

        return $data;
    }

    private function getShoppingListErrors(ShoppingList $shoppingList): array
    {
        $constraintViolationList = $this->validator->validate($shoppingList);

        if ($constraintViolationList->count()) {
            return array_map(
                static function (ConstraintViolation $constraintViolation) {
                    return $constraintViolation->getMessage();
                },
                iterator_to_array($constraintViolationList)
            );
        }

        return [];
    }
}
