<?php

namespace OroB2B\Bundle\AccountBundle\Datagrid;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class AccountActionPermissionProvider
{
    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var Registry
     */
    protected $doctrine;

    /**
     * @param SecurityFacade $securityFacade
     * @param Registry $doctrine
     */
    public function __construct(SecurityFacade $securityFacade, Registry $doctrine)
    {
        $this->securityFacade = $securityFacade;
        $this->doctrine = $doctrine;
    }

    /**
     * @param ResultRecordInterface $record
     * @param array $config
     * @return array
     */
    public function getActions(ResultRecordInterface $record, array $config)
    {
        $actions = [];

        foreach ($config as $action => $options) {
            $isGranted = true;

            if (isset($options['acl_permission']) && isset($options['acl_class'])) {

                $object = $this->findObject($options['acl_class'], $record->getValue('id'));

                $isGranted = $this->securityFacade->isGranted($options['acl_permission'], $object);
            }

            $actions[$action] = $isGranted;
        }

        return $actions;
    }

    /**
     * @param string $class
     * @param mixed $id
     * @return object
     */
    protected function findObject($class, $id)
    {
        return $this->doctrine->getManagerForClass($class)->getRepository($class)->find($id);
    }
}
