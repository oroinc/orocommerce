<?php

namespace OroB2B\Bundle\AccountBundle\Datagrid;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Extension\Action\Actions\ActionInterface;

use Oro\Bundle\SecurityBundle\Annotation\Acl as AclAnnotation;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class AccountActionPermissionProvider
{
    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var AclAnnotationProvider
     */
    protected $objectIdentityFactory;

    /**
     * @var Registry
     */
    protected $doctrine;

    /**
     * @param SecurityFacade $securityFacade
     * @param AclAnnotationProvider $annotationProvider
     * @param Registry $doctrine
     */
    public function __construct(
        SecurityFacade $securityFacade,
        AclAnnotationProvider $annotationProvider,
        Registry $doctrine
    ) {
        $this->securityFacade = $securityFacade;
        $this->annotationProvider = $annotationProvider;
        $this->doctrine = $doctrine;
    }

    /**
     * @param ResultRecordInterface $record
     * @param array $config
     * @return array
     */
    public function getUserPermissions(ResultRecordInterface $record, array $config)
    {
        $actions = [];

        foreach ($config as $action => $options) {
            $isGranted = true;

            if (null !== ($annotation = $this->getAnnotation($options))) {

                $object = $this->findObject($annotation->getClass(), $record->getValue('id'));

                $isGranted = $this->securityFacade->isGranted($annotation->getPermission(), $object);
            }
            $actions[$action] = $isGranted;
        }

        return $actions;
    }

    /**
     * @param mixed $id
     * @return object
     */
    protected function findObject($class, $id)
    {
        return $this->doctrine->getManagerForClass($class)->getRepository($class)->find($id);
    }

    /**
     * @param string $class
     * @return EntityRepository
     */
    protected function getRepository($class)
    {
        return $this->doctrine->getManagerForClass($class)->getRepository($class);
    }

    /**
     * @param array $options
     * @return null|AclAnnotation
     */
    protected function getAnnotation($options)
    {
        if (!isset($options[ActionInterface::ACL_KEY])) {
            return null;
        }

        return $this->annotationProvider->findAnnotationById($options[ActionInterface::ACL_KEY]);
    }
}
