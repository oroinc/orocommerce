<?php

namespace Oro\Bundle\SaleBundle\Model;

use Oro\Bundle\UserBundle\Entity\User;

/**
 * Defines the contract for creating {@see ContactInfo} instances.
 *
 * Implementations provide methods to create {@see ContactInfo} objects from user entities,
 * manual text input, or as empty instances ready for population.
 */
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
