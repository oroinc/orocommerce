<?php

namespace Oro\Bundle\ActionBundle\Helper;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\ActionBundle\Model\Action;
use Oro\Bundle\UserBundle\Entity\User;

class ApplicationsHelper
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param Action $action
     * @return bool
     */
    public function isApplicationsValid(Action $action)
    {
        if (!$this->tokenStorage->getToken()) {
            return false;
        }

        $applications = $action->getDefinition()->getApplications();
        if (empty($applications)) {
            return true;
        }

        $isBackendApplicationSet = in_array('backend', $applications, true);

        if ($this->isBackend()) {
            return $isBackendApplicationSet;
        }

        if ($isBackendApplicationSet) {
            unset($applications[array_search('backend', $applications, true)]);
        }

        return count($applications) > 0;
    }

    /**
     * @return bool
     */
    protected function isBackend()
    {
        $token = $this->tokenStorage->getToken();

        return $token && $token->getUser() instanceof User;
    }
}
