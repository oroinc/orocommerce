<?php

namespace Oro\Bundle\MoneyOrderBundle\Method\Config;

class MoneyOrderConfig implements MoneyOrderConfigInterface
{
    /** @var string */
    private $label;

    /** @var string */
    private $shortLabel;

    /** @var string */
    private $adminLabel;

    /** @var string */
    private $payTo;

    /** @var string */
    private $sendTo;

    /** @var string */
    private $identifier;

    /**
     * @param string $label
     * @param string $shortLabel
     * @param string $adminLabel
     * @param string $payTo
     * @param string $sendTo
     * @param string $identifier
     */
    public function __construct(
        $label,
        $shortLabel,
        $adminLabel,
        $payTo,
        $sendTo,
        $identifier
    ) {
        $this->label = $label;
        $this->shortLabel = $shortLabel;
        $this->adminLabel = $adminLabel;
        $this->payTo = $payTo;
        $this->sendTo = $sendTo;
        $this->identifier = $identifier;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return string
     */
    public function getShortLabel()
    {
        return $this->shortLabel;
    }

    /**
     * @return string
     */
    public function getAdminLabel()
    {
        return $this->adminLabel;
    }

    /**
     * @return string
     */
    public function getPayTo()
    {
        return $this->payTo;
    }

    /**
     * @return string
     */
    public function getSendTo()
    {
        return $this->sendTo;
    }

    /**
     * @return string
     */
    public function getPaymentMethodIdentifier()
    {
        return $this->identifier;
    }
}
