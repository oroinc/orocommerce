<?php

namespace OroB2B\Bundle\AccountBundle\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\SecurityBundle\Exception\ForbiddenException;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler;

class UserDeleteHandler extends DeleteHandler
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function checkPermissions($entity, ObjectManager $em)
    {
        $loggedUserId = $this->securityFacade->getLoggedUserId();
        if ($loggedUserId && $loggedUserId == $entity->getId()) {
            throw new ForbiddenException('self delete');
        }
    }
}
