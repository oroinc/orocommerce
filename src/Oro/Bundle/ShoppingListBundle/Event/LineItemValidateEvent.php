<?php

namespace Oro\Bundle\ShoppingListBundle\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityConfigBundle\Event\Event;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

/**
 * This event allows to manage validation errors for LineItems.
 * It is dispatched by LineItemCollectionValidator.
 *
 * @deprecated since 5.1, use Symfony Validator instead
 */
class LineItemValidateEvent extends Event
{
    const NAME = 'line_item.validate';

    /**
     * @var ArrayCollection
     */
    protected $errors;

    /**
     * @var ArrayCollection
     */
    protected $warnings;

    /**
     * @var
     */
    protected $lineItems;

    /**
     * @var mixed
     */
    protected $context;

    /**
     * @param LineItem[] $lineItems
     * @param mixed $context
     */
    public function __construct($lineItems, $context)
    {
        $this->lineItems = $lineItems;
        $this->context = $context;
        $this->errors = new ArrayCollection();
        $this->warnings = new ArrayCollection();
    }

    /**
     * @return ArrayCollection
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return ArrayCollection
     */
    public function getWarnings()
    {
        return $this->warnings;
    }

    /**
     * @param string $sku
     * @param string $unit
     * @param string $message
     * @param string|null $checksum LineItem::$checksum - BC fallback.
     *
     * @return $this
     */
    public function addErrorByUnit(
        string $sku,
        string $unit,
        string $message
        /*, ?string $checksum = null*/
    ): LineItemValidateEvent {
        $checksum = func_get_args()[3] ?? null;
        $this->errors->add(['sku' => $sku, 'unit' => $unit, 'checksum' => $checksum, 'message' => $message]);

        return $this;
    }

    /**
     * @param string $sku
     * @param string $unit
     * @param string $message
     * @param string|null $checksum LineItem::$checksum - BC fallback.

     * @return $this
     */
    public function addWarningByUnit(
        string $sku,
        string $unit,
        string $message
        /*, ?string $checksum = null*/
    ): LineItemValidateEvent {
        $checksum = func_get_args()[3] ?? null;
        $this->warnings->add(['sku' => $sku, 'unit' => $unit, 'checksum' => $checksum, 'message' => $message]);

        return $this;
    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        return count($this->errors) > 0;
    }

    /**
     * @return bool
     */
    public function hasWarnings()
    {
        return count($this->warnings) > 0;
    }

    /**
     * @return LineItem[]
     */
    public function getLineItems()
    {
        return $this->lineItems;
    }

    /**
     * @param LineItem[] $lineItems
     *
     * @return $this
     */
    public function setLineItems($lineItems)
    {
        $this->lineItems = $lineItems;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param mixed $context
     *
     * @return $this
     */
    public function setContext($context)
    {
        $this->context = $context;

        return $this;
    }
}
