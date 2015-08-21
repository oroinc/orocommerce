<?php

namespace OroB2B\src\OroB2B\Bundle\AccountBundle\EventListener;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class FrontendDatagridListener
{
    /**
     * @var TokenStorageInterface
     */
    protected $securityTokenStorage;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param SecurityFacade        $securityFacade
     */
    public function __construct(TokenStorageInterface $tokenStorage, SecurityFacade $securityFacade)
    {
        $this->securityTokenStorage = $tokenStorage;
        $this->securityFacade = $securityFacade;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();
        $user = $this->getUser();

        if ($this->securityFacade->isGranted('orob2b_account_account_user_role_frontend_view') && $user) {
            $andWhere = 'role.account IN (' . $user->getId() . ')';
            $this->addConfigElement($config, '[source][query][where][and]', $andWhere);

            $orWhere = 'role.account IS NULL';
            $this->addConfigElement($config, '[source][query][where][or]', $orWhere);
        } else {
            $this->addConfigElement($config, '[source][query][where][and]', '1=0');
        }
    }

    /**
     * @return object|void
     */
    protected function getUser()
    {
        if (null === $token = $this->securityTokenStorage->getToken()) {
            return;
        }

        if (!is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            return;
        }

        return $user;
    }

    /**
     * @param DatagridConfiguration $config
     * @param string $path
     * @param mixed $element
     */
    protected function addConfigElement(DatagridConfiguration $config, $path, $element)
    {
        $select = $config->offsetGetByPath($path);
        $select[] = $element;
        $config->offsetSetByPath($path, $select);
    }
}
