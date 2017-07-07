<?php

namespace Oro\Bundle\SaleBundle\Model;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class ContactInfo extends ParameterBag
{
    /**
     * @internal
     */
    const NAME = 'name';

    /**
     * @internal
     */
    const PHONE = 'phone';

    /**
     * @internal
     */
    const EMAIL = 'email';

    /**
     * @internal
     */
    const MANUAL_TEXT = 'manual_text';

    /**
     * @return string
     */
    public function getName()
    {
        return $this->get(static::NAME);
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->set(static::NAME, $name);

        return $this;
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->get(static::PHONE);
    }

    /**
     * @param string $phone
     *
     * @return $this
     */
    public function setPhone($phone)
    {
        $this->set(static::PHONE, $phone);

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->get(static::EMAIL);
    }

    /**
     * @param string $email
     *
     * @return $this
     */
    public function setEmail($email)
    {
        $this->set(static::EMAIL, $email);

        return $this;
    }

    /**
     * @return string
     */
    public function getManualText()
    {
        return $this->get(static::MANUAL_TEXT);
    }

    /**
     * @param string $manualText
     *
     * @return $this
     */
    public function setManualText($manualText)
    {
        $this->set(static::MANUAL_TEXT, $manualText);

        return $this;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->all());
    }

    /**
     * {@inheritDoc}
     */
    public function get($name)
    {
        return $this->has($name) ? parent::get($name) : '';
    }
}
