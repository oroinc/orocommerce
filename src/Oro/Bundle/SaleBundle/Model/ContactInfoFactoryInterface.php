<?php

namespace Oro\Bundle\SaleBundle\Model;

use Oro\Bundle\UserBundle\Entity\User;

interface ContactInfoFactoryInterface
{
    /**
     * @param User $user
     *
     * @return ContactInfo
     */
    public function createContactInfoByUser(User $user);

    /**
     * @param string $text
     *
     * @return ContactInfo
     */
    public function createContactInfoWithText($text);

    /**
     * @return ContactInfo
     */
    public function createContactInfo();
}
