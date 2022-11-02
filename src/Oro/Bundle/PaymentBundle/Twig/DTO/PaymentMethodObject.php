<?php

namespace Oro\Bundle\PaymentBundle\Twig\DTO;

/**
 * Data transfer object class to provide backward compatibility for email templates using __toString method
 */
class PaymentMethodObject
{
    /**
     * @var string
     */
    private $label;

    /**
     * @var array
     */
    private $options;

    public function __construct(string $label, array $options)
    {
        $this->label = $label;
        $this->options = $options;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->label;
    }
}
