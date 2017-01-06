<?php

namespace Oro\Bundle\PayPalBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="oro_paypal_ec_payment_action")
 * @ORM\Entity
 */
class ExpressCheckoutPaymentAction
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
     * @ORM\Column(name="label", type="string", length=255, nullable=true)
     */
    protected $label;

    /**
     * @var PayPalSettings
     *
     * @ORM\ManyToOne(
     *     targetEntity="Oro\Bundle\PayPalBundle\Entity\PayPalSettings",
     *     inversedBy="expressCheckoutPaymentAction"
     * )
     * @ORM\JoinColumn(name="settings_id", referencedColumnName="id", onDelete="CASCADE")
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
     * @return ExpressCheckoutPaymentAction
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
     * Set payPalSettings
     *
     * @param PayPalSettings $payPalSettings
     *
     * @return ExpressCheckoutPaymentAction
     */
    public function setPayPalSettings(PayPalSettings $payPalSettings = null)
    {
        $this->payPalSettings = $payPalSettings;

        return $this;
    }

    /**
     * Get payPalSettings
     *
     * @return PayPalSettings
     */
    public function getPayPalSettings()
    {
        return $this->payPalSettings;
    }
}
