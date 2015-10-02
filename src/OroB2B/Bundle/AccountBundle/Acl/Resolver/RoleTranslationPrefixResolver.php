<?php

namespace OroB2B\Bundle\AccountBundle\Acl\Resolver;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

class RoleTranslationPrefixResolver
{
    const BACKEND_PREFIX = 'orob2b.account.security.access-level.';
    const FRONTEND_PREFIX = 'orob2b.account.frontend.security.access-level.';

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * @return string
     * @throws \RuntimeException
     */
    public function getPrefix()
    {
        $user = $this->securityFacade->getLoggedUser();

        if ($user instanceof User) {
            return self::BACKEND_PREFIX;
        } elseif ($user instanceof AccountUser) {
            return self::FRONTEND_PREFIX;
        }

        throw new \RuntimeException('This method must be called only for logged User or AccountUser');
    }
}
