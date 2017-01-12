<?php

namespace Oro\Bundle\PayPalBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="oro_paypal_cc_payment_action")
 * @ORM\Entity
 */
class CreditCardPaymentAction
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;


    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=255, nullable=false)
     */
    protected $label;


    /**
     * @var PayPalSettings[]|Collection
     *
     * @ORM\OneToMany(
     *      targetEntity="Oro\Bundle\PayPalBundle\Entity\PayPalSettings",
     *      mappedBy="creditCardPaymentAction",
     *      cascade={"all"},
     *      orphanRemoval=true
     * )
     */
    protected $payPalSettings;

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
     * @return CreditCardPaymentAction
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

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->payPalSettings = new ArrayCollection();
    }

    /**
     * Add payPalSetting
     *
     * @param PayPalSettings $payPalSetting
     *
     * @return CreditCardPaymentAction
     */
    public function addPayPalSetting(PayPalSettings $payPalSetting)
    {
        if (!$this->payPalSettings->contains($payPalSetting)) {
            $this->payPalSettings->add($payPalSetting);
        }

        return $this;
    }

    /**
     * Remove payPalSetting
     *
     * @param PayPalSettings $payPalSetting
     *
     * @return CreditCardPaymentAction
     */
    public function removePayPalSetting(PayPalSettings $payPalSetting)
    {
        if ($this->payPalSettings->contains($payPalSetting)) {
            $this->payPalSettings->removeElement($payPalSetting);
        }

        return $this;
    }

    /**
     * Get payPalSettings
     *
     * @return Collection
     */
    public function getPayPalSettings()
    {
        return $this->payPalSettings;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getLabel();
    }
}
