<?php

namespace OroB2B\Bundle\AccountBundle\Owner;

use Oro\Bundle\SecurityBundle\Owner\AbstractEntityOwnershipDecisionMaker;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

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
