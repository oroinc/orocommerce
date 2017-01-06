<?php

namespace Oro\Bundle\PayPalBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="oro_paypal_credit_card_types")
 * @ORM\Entity
 */
class CreditCardTypes
{
    const CARD_VISA = 'visa';
    const CARD_MASTERCARD = 'mastercard';
    const CARD_DISCOVER = 'discover';
    const CARD_AMERICAN_EXPRESS = 'american_express';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=255, nullable=true)
     */
    protected $label;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set label
     *
     * @param string $label
     *
     * @return CreditCardTypes
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }
}
