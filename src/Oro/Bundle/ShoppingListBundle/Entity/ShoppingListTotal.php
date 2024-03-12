<?php

namespace Oro\Bundle\ShoppingListBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemNotPricedSubtotalProvider;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListTotalRepository;

/**
 * Entity for a caching shopping list subtotals data by currency
 * If isValid=false values should be recalculated
 **/
#[ORM\Entity(repositoryClass: ShoppingListTotalRepository::class)]
#[ORM\Table(name: 'oro_shopping_list_total')]
#[ORM\UniqueConstraint(
    name: 'unique_shopping_list_currency_customer_user',
    columns: ['shopping_list_id', 'currency', 'customer_user_id']
)]
class ShoppingListTotal
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ShoppingList::class, inversedBy: 'totals')]
    #[ORM\JoinColumn(name: 'shopping_list_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?ShoppingList $shoppingList = null;

    #[ORM\Column(name: 'currency', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $currency = null;

    /**
     * @var float
     */
    #[ORM\Column(name: 'subtotal_value', type: 'money', nullable: true)]
    protected $subtotalValue;

    #[ORM\Column(name: 'is_valid', type: Types::BOOLEAN)]
    protected ?bool $valid = false;

    #[ORM\ManyToOne(targetEntity: CustomerUser::class)]
    #[ORM\JoinColumn(name: 'customer_user_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?CustomerUser $customerUser = null;

    /**
     * @param ShoppingList $shoppingList
     * @param string $currency
     */
    public function __construct(ShoppingList $shoppingList, $currency)
    {
        $this->shoppingList = $shoppingList;
        # By default, the owner is associated with the owner of the shopping list.
        $this->customerUser = $shoppingList->getCustomerUser();
        $this->currency = $currency;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return ShoppingList
     */
    public function getShoppingList()
    {
        return $this->shoppingList;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @return boolean
     */
    public function isValid()
    {
        return $this->valid;
    }

    /**
     * @param boolean $valid
     * @return $this
     */
    public function setValid($valid)
    {
        $this->valid = $valid;

        return $this;
    }

    /**
     * @param Subtotal $subtotal
     * @return $this
     */
    public function setSubtotal(Subtotal $subtotal)
    {
        if ($subtotal->getCurrency() !== $this->currency) {
            throw new \InvalidArgumentException();
        }

        $this->subtotalValue = $subtotal->getAmount();

        return $this;
    }

    /**
     * @return Subtotal
     */
    public function getSubtotal()
    {
        $subtotal = new Subtotal();
        $subtotal->setAmount($this->subtotalValue)
            ->setCurrency($this->currency)
            ->setType(LineItemNotPricedSubtotalProvider::TYPE)
            ->setLabel(LineItemNotPricedSubtotalProvider::LABEL);

        return $subtotal;
    }

    public function getCustomerUser(): ?CustomerUser
    {
        return $this->customerUser;
    }

    public function setCustomerUser(?CustomerUser $customerUser): self
    {
        $this->customerUser = $customerUser;

        return $this;
    }
}
