<?php

namespace Oro\Bundle\CustomerBundle\Placeholder;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;

class PlaceholderFilter
{
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
     * @return bool
     */
    public function isUserApplicable()
    {
        return $this->securityFacade->getLoggedUser() instanceof CustomerUser;
    }

    /**
     * @return bool
     */
    public function isLoginRequired()
    {
        return !is_object($this->securityFacade->getLoggedUser());
    }

    /**
     * @return bool
     */
    public function isFrontendApplicable()
    {
        $user = $this->securityFacade->getLoggedUser();

        return !is_object($user) || $user instanceof CustomerUser;
    }
}
