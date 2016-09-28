<?php

namespace Oro\Bundle\AccountBundle\Tests\Selenium\Entity;

class SeleniumAddress
{
    /** @var string */
    public $street;

    /** @var string */
    public $city;

    /** @var string */
    public $county;

    /** @var string */
    public $state;

    /** @var int */
    public $zip;

    /** @var bool */
    public $isBilling;

    /** @var bool */
    public $isShipping;

    /** @var bool */
    public $isDefaultBilling;

    /** @var bool */
    public $isDefaultShipping;

    /**
     * SeleniumAddress constructor.
     *
     * @param string|null $street
     * @param string|null $city
     * @param string|null $county
     * @param string|null $state
     * @param string|null $zip
     * @param bool $isBilling
     * @param bool $isShipping
     * @param bool $isDefaultBilling
     * @param bool $isDefaultShipping
     */
    public function __construct(
        $street = null,
        $city = null,
        $county = null,
        $state = null,
        $zip = null,
        $isBilling = false,
        $isShipping = false,
        $isDefaultBilling = false,
        $isDefaultShipping = false
    ) {
        $this->street = $street;
        $this->city = $city;
        $this->county = $county;
        $this->state = $state;
        $this->zip = $zip;
        $this->isBilling = $isBilling;
        $this->isShipping = $isShipping;
        $this->isDefaultBilling = $isDefaultBilling;
        $this->isDefaultShipping = $isDefaultShipping;
    }
}
