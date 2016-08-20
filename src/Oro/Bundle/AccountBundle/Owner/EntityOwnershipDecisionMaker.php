<?php

namespace Oro\Bundle\AccountBundle\Owner;

use Oro\Bundle\SecurityBundle\Owner\AbstractEntityOwnershipDecisionMaker;
use Oro\Bundle\AccountBundle\Entity\AccountUser;

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
