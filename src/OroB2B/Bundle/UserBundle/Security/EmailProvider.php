<?php

namespace OroB2B\Bundle\UserBundle\Security;

use FOS\UserBundle\Security\UserProvider;

class EmailProvider extends UserProvider
{
    /**
     * {@inheritDoc}
     */
    protected function findUser($email)
    {
        return $this->userManager->findUserByEmail($email);
    }
}
