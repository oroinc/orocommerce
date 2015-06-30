<?php

namespace OroB2B\Bundle\CustomerBundle\Owner;

use Oro\Bundle\SecurityBundle\Owner\AbstractEntityOwnershipDecisionMaker;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;

class EntityOwnershipDecisionMaker extends AbstractEntityOwnershipDecisionMaker
{
    /**
     * {@inheritdoc}
     */
    public function supports()
    {
        return $this->getContainer()->get('oro_security.security_facade')->getLoggedUser() instanceof AccountUser;
    }
}
