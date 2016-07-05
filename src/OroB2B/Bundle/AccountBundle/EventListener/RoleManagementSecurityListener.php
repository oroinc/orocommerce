<?php

namespace OroB2B\Bundle\AccountBundle\EventListener;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountUserRoleRepository;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class RoleManagementSecurityListener
{
    /**
     * @var SecurityFacade
     */
    private $securityFacade;

    /**
     * @var AccountUserRoleRepository
     */
    private $accountUserRoleRepository;

    /**
     * @param SecurityFacade $securityFacade
     * @param AccountUserRoleRepository $accountUserRoleRepository
     */
    public function __construct(SecurityFacade $securityFacade, AccountUserRoleRepository $accountUserRoleRepository)
    {
        $this->securityFacade = $securityFacade;
        $this->accountUserRoleRepository = $accountUserRoleRepository;
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $route = $event->getRequest()->attributes->get('_route');
        $routeParams = $event->getRequest()->attributes->get('_route_params');

        if (!isset($routeParams['id'])) {
            return;
        }

        $isGranted = true;

        if ($route == 'orob2b_account_frontend_account_user_role_view') {
            $role = $this->loadRole($routeParams['id']);

            $isGranted = (
                $role->isPredefined()
                    ? $this->securityFacade->isGranted('orob2b_account_frontend_account_user_role_view')
                    : $this->securityFacade->isGranted('FRONTEND_ACCOUNT_ROLE_VIEW', $role)
                ) && $role->isSelfManaged();
        }

        if ($route == 'orob2b_account_frontend_account_user_role_update') {
            $role = $this->loadRole($routeParams['id']);

            $isGranted = (
                $role->isPredefined()
                    ? $this->securityFacade->isGranted('orob2b_account_frontend_account_user_role_create')
                    : $this->securityFacade->isGranted('FRONTEND_ACCOUNT_ROLE_UPDATE', $role)
                ) && $role->isSelfManaged();
        }

        if (!$isGranted) {
            throw new AccessDeniedHttpException('You don\t have enough permission to access this page.');
        }
    }

    /**
     * @param $id
     * @return null|object
     * @throws \DomainException
     */
    private function loadRole($id)
    {
        $role = $this->accountUserRoleRepository->find($id);

        if (!$role) {
            throw new \DomainException('Role not found with id '.$id);
        }

        return $role;
    }
}
