<?php

namespace OroB2B\Bundle\CustomerBundle\Placeholder;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;

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
        return $this->securityFacade->getLoggedUser() instanceof AccountUser;
    }

    /**
     * @return bool
     */
    public function isLoginRequired()
    {
        return !is_object($this->securityFacade->getLoggedUser());
    }
}
