<?php

namespace Oro\Bundle\CustomerBundle\Acl\Resolver;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;

class RoleTranslationPrefixResolver
{
    const BACKEND_PREFIX = 'oro.customer.security.access-level.';
    const FRONTEND_PREFIX = 'oro.customer.frontend.security.access-level.';

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
