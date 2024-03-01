<?php

namespace Oro\Bundle\TaxBundle\Model;

use Brick\Math\BigDecimal;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

/**
 * Object which holds all data related to tax such as line items, destination, amount, etc.
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class Taxable
{
    public const DIGITAL_PRODUCT = 'digital_product';
    public const PRODUCT_TAX_CODE = 'product_tax_code';
    public const ACCOUNT_TAX_CODE = 'customer_tax_code';

    protected ?int $identifier = null;

    protected ?string $className = null;

    protected ?AbstractAddress $origin = null;

    protected ?AbstractAddress $destination = null;

    protected ?AbstractAddress $taxationAddress = null;

    protected BigDecimal $quantity;

    protected BigDecimal $price;

    protected BigDecimal $amount;

    protected ?BigDecimal $shippingCost = null;

    /**
     * @var \SplObjectStorage|Taxable[]
     */
    protected \SplObjectStorage $items;

    protected Result $result;

    protected ?string $currency = null;

    protected \ArrayObject $context;

    protected bool $kitTaxable = false;

    public function __construct()
    {
        $this->quantity = BigDecimal::one();
        $this->price = BigDecimal::zero();
        $this->amount = BigDecimal::zero();
        $this->shippingCost = null;

        $this->items = new \SplObjectStorage();
        $this->result = new Result();
        $this->context = new \ArrayObject();
    }

    public function getIdentifier(): ?int
    {
        return $this->identifier;
    }

    public function setIdentifier(?int $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getOrigin(): ?AbstractAddress
    {
        return $this->origin;
    }

    public function setOrigin(?AbstractAddress $origin = null): self
    {
        $this->origin = $origin;

        return $this;
    }

    public function getDestination(): ?AbstractAddress
    {
        return $this->destination;
    }

    public function setDestination(?AbstractAddress $destination = null): self
    {
        $this->destination = $destination;

        return $this;
    }

    public function getQuantity(): BigDecimal
    {
        return $this->quantity;
    }

    public function setQuantity(BigDecimal|float|int|string $quantity): self
    {
        $this->quantity = BigDecimal::of($quantity);

        return $this;
    }

    public function getPrice(): BigDecimal
    {
        return $this->price;
    }

    public function setPrice(BigDecimal|float|int|string $price): self
    {
        $this->price = BigDecimal::of($price);

        return $this;
    }

    public function getAmount(): BigDecimal
    {
        return $this->amount;
    }

    public function setAmount(BigDecimal|float|int|string $amount): self
    {
        $this->amount = BigDecimal::of($amount);

        return $this;
    }

    public function getShippingCost(): ?BigDecimal
    {
        return $this->shippingCost;
    }

    public function setShippingCost(BigDecimal|float|int|string $shippingCost): self
    {
        $this->shippingCost = BigDecimal::of($shippingCost);

        return $this;
    }

    public function getItems(): \SplObjectStorage
    {
        $this->items->rewind();

        return $this->items;
    }

    public function setItems(\SplObjectStorage $items): self
    {
        $this->items = $items;

        return $this;
    }

    public function addItem(Taxable $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items->attach($item);
        }

        return $this;
    }

    public function removeItem(Taxable $item): self
    {
        if ($this->items->contains($item)) {
            $this->items->detach($item);
        }

        return $this;
    }

    public function setClassName(string $className): self
    {
        $this->className = $className;

        return $this;
    }

    public function getClassName(): ?string
    {
        return $this->className;
    }

    public function getResult(): Result
    {
        return $this->result;
    }

    public function setResult(Result $result): self
    {
        if ($this->result->count() === 0) {
            $this->result = $result;
        }

        return $this;
    }

    public function setContext(\ArrayObject $arrayObject): self
    {
        $this->context = $arrayObject;

        return $this;
    }

    public function addContext(string $keyName, mixed $value): self
    {
        $this->context->offsetSet($keyName, $value);

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getContext(): \ArrayObject
    {
        return $this->context;
    }

    public function getContextValue(string $keyName): mixed
    {
        if ($this->context->offsetExists($keyName)) {
            return $this->context->offsetGet($keyName);
        }

        return null;
    }

    public function getTaxationAddress(): ?AbstractAddress
    {
        return $this->taxationAddress;
    }

    public function setTaxationAddress(AbstractAddress $taxationAddress = null): self
    {
        $this->taxationAddress = $taxationAddress;

        return $this;
    }

    public function makeDestinationAddressTaxable(): self
    {
        $this->taxationAddress = $this->destination;

        return $this;
    }

    public function makeOriginAddressTaxable(): self
    {
        $this->taxationAddress = $this->origin;

        return $this;
    }

    public function isKitTaxable(): bool
    {
        return $this->kitTaxable;
    }

    public function setKitTaxable(bool $kitTaxable): self
    {
        $this->kitTaxable = $kitTaxable;

        return $this;
    }

    public function __clone()
    {
        $propertiesToExplicitClone = ['price', 'taxationAddress', 'origin', 'amount', 'quantity', 'shippingCost',
            'result', 'destination'];

        foreach ($propertiesToExplicitClone as $property) {
            $this->$property = is_object($this->$property) ? clone $this->$property : null;
        }

        $newItemStorage = new \SplObjectStorage();
        $this->items->rewind();
        foreach ($this->items as $item) {
            $newItemStorage->attach(clone $item);
        }
        $this->items = $newItemStorage;
    }
}
