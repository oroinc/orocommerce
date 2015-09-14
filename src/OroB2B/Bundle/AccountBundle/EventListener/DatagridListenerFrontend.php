<?php

namespace OroB2B\Bundle\AccountBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

class DatagridListenerFrontend
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
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();
        $user = $this->securityFacade->getLoggedUser();

        if ($user instanceof AccountUser &&
            $user->getAccount() &&
            $this->securityFacade->isGranted('orob2b_account_frontend_account_user_role_view')
        ) {
            $andWhere = 'role.account IN (' . $user->getAccount()->getId() . ')';
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
