<?php

namespace OroB2B\src\OroB2B\Bundle\AccountBundle\EventListener;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Entity\Account;

class DatagridListenerFrontend
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

        /** @var Account $account */
        $account = $this->securityFacade->getLoggedUser() ? $this->securityFacade->getLoggedUser()->getAccount() : null;

        if ($this->securityFacade->isGranted('orob2b_account_account_user_role_frontend_view') && $account) {
            $andWhere = 'role.account IN (' . $account->getId() . ')';
            $this->addConfigElement($config, '[source][query][where][and]', $andWhere);

            $orWhere = 'role.account IS NULL';
            $this->addConfigElement($config, '[source][query][where][or]', $orWhere);
        } else {
            $this->addConfigElement($config, '[source][query][where][and]', '1=0');
        }
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
