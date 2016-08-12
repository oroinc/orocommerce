<?php

namespace OroB2B\Bundle\AccountBundle\Entity;

interface AddressPhoneAwareInterface
{
    /**
     * Get phone number
     *
     * @return string
     */
    public function getPhone();

    /**
     * Set phone number
     *
     * @param string $phone
     * @return $this
     */
    public function setPhone($phone);
}
