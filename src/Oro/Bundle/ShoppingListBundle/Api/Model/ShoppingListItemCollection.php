<?php

namespace Oro\Bundle\ShoppingListBundle\Api\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Util\EntityInstantiator;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * The collection of shopping list items that is used to implement "add to cart" functionality,
 * that means that if a product is added to a shopping list and this product is already associated
 * with an existing shopping list item, the quantity of this item will be a sum of submitted and existing quantities.
 */
class ShoppingListItemCollection extends ArrayCollection
{
    private ShoppingList $shoppingList;
    private EntityInstantiator $entityInstantiator;
    private string $entityClass;
    private EntityDefinitionConfig $entityConfig;
    /** @var Collection<int, LineItem> */
    private Collection $existingItems;
    private ValidatorInterface $validator;
    /** @var LineItem[] */
    private array $submittedItems = [];

    /**
     * @param ShoppingList              $shoppingList
     * @param EntityInstantiator        $entityInstantiator
     * @param string                    $entityClass
     * @param EntityDefinitionConfig    $entityConfig
     * @param Collection<int, LineItem> $existingItems
     * @param ValidatorInterface        $validator
     */
    public function __construct(
        ShoppingList $shoppingList,
        EntityInstantiator $entityInstantiator,
        string $entityClass,
        EntityDefinitionConfig $entityConfig,
        Collection $existingItems,
        ValidatorInterface $validator
    ) {
        parent::__construct();
        $this->shoppingList = $shoppingList;
        $this->entityInstantiator = $entityInstantiator;
        $this->entityClass = $entityClass;
        $this->entityConfig = $entityConfig;
        $this->existingItems = $existingItems;
        $this->validator = $validator;
    }

    /**
     * Gets an array contains all submitted line items.
     */
    public function toArray(): array
    {
        return $this->submittedItems;
    }

    /**
     * Returns a shopping list item that should be linked to the given form.
     */
    public function getItem(FormInterface $form): LineItem
    {
        $item = $this->findItem($form);
        if (null === $item) {
            // the submitted data represent a new shopping list item
            $item = $this->createNewItem();
        } else {
            // the submitted data represent an existing shopping list item
            $this->updateExistingItemForm($form, $item);
        }

        return $item;
    }

    /**
     * {@inheritDoc}
     */
    public function set($key, $value)
    {
        parent::set($key, $value);
        if (!$this->existingItems->contains($value)) {
            $this->completeNewItem($value);
            $this->existingItems->add($value);
        }
        $this->submittedItems[] = $value;
    }

    protected function updateExistingItemForm(FormInterface $form, LineItem $item): void
    {
        // update the model data of the quantity form field
        // as a sum of submitted and existing quantities
        $quantityForm = $this->getForm($form, 'quantity');
        if (null === $quantityForm) {
            return;
        }
        if ($this->isValidQuantity($quantityForm, $item)) {
            $quantity = $quantityForm->getData();
            if (\is_numeric($quantity)) {
                $quantity += $item->getQuantity();
                // set the updated quantity back to the form,
                // to allow validators to check this value,
                // e.g. there may be a validator that limit a max value of the quantity
                $this->setFormData($quantityForm, $quantity);
            }
        } elseif (!$quantityForm->isSubmitted()) {
            // set the quantity value to NULL if the quantity field was not submitted
            // it is required for because the default value for the quantity is equal to 1,
            // but it is expected that the quantity field is required in submitted data
            // @see \Oro\Bundle\ShoppingListBundle\Entity\LineItem::$quantity
            $item->setQuantity(null);
        }
    }

    protected function isValidQuantity(FormInterface $quantityForm, LineItem $item): bool
    {
        $isValid = true;
        $validationGroups = $quantityForm->getRoot()->getConfig()->getOption('validation_groups');
        $originalQuantity = $item->getQuantity();
        $quantityToValidate = null;
        if ($quantityForm->isSubmitted()) {
            $quantityToValidate = $quantityForm->getData();
        }
        try {
            $item->setQuantity($quantityToValidate);
            /** @var ConstraintViolation[] $violations */
            $violations = $this->validator->validate($item, null, $validationGroups);
            foreach ($violations as $violation) {
                if ('quantity' === $violation->getPropertyPath()) {
                    $isValid = false;
                    break;
                }
            }
        } finally {
            $item->setQuantity($originalQuantity);
        }

        return $isValid;
    }

    protected function createNewItem(): LineItem
    {
        /** @var LineItem $item */
        $item = $this->entityInstantiator->instantiate($this->entityClass);
        // set the quantity value to NULL
        // it is required for because the default value for the quantity is equal to 1,
        // but it is expected that the quantity field is required in submitted data
        // @see \Oro\Bundle\ShoppingListBundle\Entity\LineItem::$quantity
        $item->setQuantity(null);

        return $item;
    }

    protected function completeNewItem(LineItem $item): void
    {
        $item->setShoppingList($this->shoppingList);
        /**
         * Ensures that a customer user and a organization are assigned to the processing line item.
         * If a customer user or a organization are not assigned to the line item,
         * the shopping list's customer user and organization are assigned to it.
         * It is required because there is no Doctrine listener that does these assignments.
         */
        if (null === $item->getCustomerUser() && null !== $this->shoppingList->getCustomerUser()) {
            $item->setCustomerUser($this->shoppingList->getCustomerUser());
        }
        if (null === $item->getOrganization() && null !== $this->shoppingList->getOrganization()) {
            $item->setOrganization($this->shoppingList->getOrganization());
        }
    }

    protected function findItem(FormInterface $form): ?LineItem
    {
        $existingItem = null;
        foreach ($this->existingItems as $value) {
            if ($this->areItemsEqual($value, $form)) {
                $existingItem = $value;
                break;
            }
        }

        return $existingItem;
    }

    protected function areItemsEqual(LineItem $existingItem, FormInterface $form): bool
    {
        return
            $this->areProductsEqual($existingItem, $form)
            && $this->areParentProductsEqual($existingItem, $form)
            && $this->areUnitsEqual($existingItem, $form);
    }

    protected function areProductsEqual(LineItem $existingItem, FormInterface $form): bool
    {
        /** @var Product|null $unit */
        $product = $this->getFormValue($form, 'product');
        $existingProduct = $existingItem->getProduct();

        return
            null !== $product
            && null !== $existingProduct
            && $product->getId() === $existingProduct->getId();
    }

    protected function areParentProductsEqual(LineItem $existingItem, FormInterface $form): bool
    {
        /** @var Product|null $unit */
        $parentProduct = $this->getFormValue($form, 'parentProduct');
        $existingParentProduct = $existingItem->getParentProduct();

        return
            (null === $parentProduct && null === $existingParentProduct)
            || (
                null !== $parentProduct
                && null !== $existingParentProduct
                && $parentProduct->getId() === $existingParentProduct->getId()
            );
    }

    protected function areUnitsEqual(LineItem $existingItem, FormInterface $form): bool
    {
        /** @var ProductUnit|null $unit */
        $unit = $this->getFormValue($form, 'unit');
        $existingUnit = $existingItem->getUnit();

        return
            null !== $unit
            && null !== $existingUnit
            && $unit->getCode() === $existingUnit->getCode();
    }

    protected function getForm(FormInterface $form, string $fieldName): ?FormInterface
    {
        $name = $this->entityConfig->findFieldNameByPropertyPath($fieldName);
        if (!$name) {
            $name = $fieldName;
        }

        if (!$form->has($name)) {
            return null;
        }

        return $form->get($name);
    }

    protected function getFormValue(FormInterface $form, string $fieldName): mixed
    {
        $field = $this->getForm($form, $fieldName);
        if (null === $field) {
            return null;
        }

        return $field->getData();
    }

    protected function setFormData(FormInterface $form, mixed $value): void
    {
        // the Form::setData() cannot be used because the form is already locked
        $updateQuantityFormClosure = \Closure::bind(
            function ($form) use ($value) {
                $form->modelData = $value;
            },
            null,
            $form
        );
        $updateQuantityFormClosure($form);
    }
}
