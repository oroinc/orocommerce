<?php

namespace Oro\Bundle\CustomerBundle\Helper;

use Symfony\Component\PropertyAccess\PropertyAccessor;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Entity\Repository\AccountUserRepository;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class CustomerUserHelper
{
    const OWNER_TYPE_USER = 'FRONTEND_USER';
    const OWNER_TYPE_ACCOUNT = 'FRONTEND_ACCOUNT';

    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * CustomerUserHelper constructor.
     * @param Registry $registry
     * @param ConfigProvider $configProvider
     * @param PropertyAccessor $propertyAccessor
     */
    public function __construct(
        Registry $registry,
        ConfigProvider $configProvider,
        PropertyAccessor $propertyAccessor
    ) {
        $this->registry = $registry;
        $this->configProvider  = $configProvider;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param AccountUser $user
     * @param $entity
     * @param bool $emptyField
     */
    public function setAccountUser(AccountUser $user, $entity, $emptyField = false)
    {
        $className = ClassUtils::getClass($entity);
        if ($this->configProvider->hasConfig($className)) {
            $config = $this->configProvider->getConfig($className);
            $frontendOwnerType = $config->get('frontend_owner_type');
            $ownerFieldName = $config->get('frontend_owner_field_name');
            if ($emptyField) {
                $emptyField = $this->getAccessorValue($entity, $ownerFieldName);
            }
            // set default owner for organization and user owning entities
            if ($frontendOwnerType
                && in_array($frontendOwnerType, [self::OWNER_TYPE_USER, self::OWNER_TYPE_ACCOUNT], true)
                && !$emptyField
            ) {
                $owner = null;
                if ($frontendOwnerType === self::OWNER_TYPE_USER) {
                    $owner = $user;
                }
                if ($frontendOwnerType === self::OWNER_TYPE_ACCOUNT) {
                    $owner = $user->getAccount();
                }
                $this->propertyAccessor->setValue(
                    $entity,
                    $ownerFieldName,
                    $owner
                );
            }
        }
    }

    /**
     * @param $entity
     * @return mixed|null
     */
    public function getOwnerFieldName($entity)
    {
        $className = ClassUtils::getClass($entity);
        $config = $this->configProvider->getConfig($className);
        return $config->get('frontend_owner_field_name');
    }

    /**
     * @param $entity
     * @param $ownerFieldName
     * @return mixed
     */
    public function getAccessorValue($entity, $ownerFieldName)
    {
        return $this->propertyAccessor->getValue($entity, $ownerFieldName);
    }

    /**
     * @return AccountUserRepository
     */
    private function getAccountUsersRepository()
    {
        return $this->registry
            ->getManagerForClass('OroCustomerBundle:AccountUser')
            ->getRepository('OroCustomerBundle:AccountUser');
    }

    /**
     * @param $id
     * @return null|object
     */
    public function getUserById($id)
    {
        return $this->getAccountUsersRepository()->findOneBy(['id' => $id]);
    }

    /**
     * @param AclHelper $aclHelper
     * @return array
     */
    public function getAccountUsers(AclHelper $aclHelper)
    {
        return $this->getAccountUsersRepository()->getAccountUsers($aclHelper);
    }
}
