<?php

namespace Oro\Bundle\CustomerBundle\Tests\Behat\Context;

use Behat\Symfony2Extension\Context\KernelDictionary;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\UserBundle\Entity\Role;

class CustomerUserContext extends OroFeatureContext
{
    use KernelDictionary;

    /**
     * Example: AmandaRCole@example.org customer user has Buyer role
     *
     * @Given /^(?P<username>\S+) customer user has (?P<role>[\w\s]+) role$/
     *
     * @param string $username
     * @param string $role
     */
    public function customerUserHasRole($username, $role)
    {
        $customerUser = $this->getCustomerUser($username);
        self::assertNotNull($customerUser, sprintf('Could not found customer user "%s",', $username));

        $customerUserRole = null;

        /** @var Role $item */
        foreach ($customerUser->getRoles() as $item) {
            if ($role === $item->getLabel()) {
                $customerUserRole = $item;
                break;
            }
        }

        self::assertNotNull(
            $customerUserRole,
            sprintf('Customer user "%s" was found, but without role "%s"', $username, $role)
        );
    }

    /**
     * @param string $username
     * @return CustomerUser
     */
    protected function getCustomerUser($username)
    {
        return $this->getRepository(CustomerUser::class)->findOneBy(['username' => $username]);
    }

    /**
     * @param string $className
     * @return ObjectRepository
     */
    protected function getRepository($className)
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass($className)
            ->getRepository($className);
    }
}
