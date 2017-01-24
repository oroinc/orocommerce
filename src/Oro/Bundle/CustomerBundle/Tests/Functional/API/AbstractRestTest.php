<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\API;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\Repository\CustomerRepository;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\UserBundle\Entity\BaseUserManager;

abstract class AbstractRestTest extends RestJsonApiTestCase
{
    use UserUtilityTrait;

    const DATE_FORMAT = 'Y-m-d\TH:i:s\Z';

    /**
     * @param string $name
     * @return CustomerGroup
     */
    protected function createCustomerGroup($name)
    {
        $group = new CustomerGroup();
        $group->setName($name);

        $this->getManager()->persist($group);
        $this->getManager()->flush();

        return $group;
    }

    /**
     * @param string $name
     * @param null|string $ratingId
     * @param CustomerGroup $group
     * @return Customer
     */
    protected function createCustomer($name, CustomerGroup $group = null, $ratingId = null)
    {
        $manager = $this->getManager();
        $owner = $this->getFirstUser($manager);
        $parent = $manager->getRepository(Customer::class)->findOneByName('CustomerUser CustomerUser');

        $customer = new Customer();
        $customer->setName($name)
            ->setOwner($owner)
            ->setOrganization($owner->getOrganization())
            ->setParent($parent)
            ->addSalesRepresentative($owner);

        if ($ratingId) {
            $customer->setInternalRating($this->getRating($ratingId));
        }
        if ($group) {
            $group->addCustomer($customer);
        }

        $manager->persist($customer);
        $manager->flush();

        return $customer;
    }

    /**
     * @param string $email
     * @param Customer|null $customer
     * @return CustomerUser
     */
    protected function createCustomerUser($email, Customer $customer = null)
    {
        /** @var BaseUserManager $userManager */
        $userManager = $this->getContainer()->get('oro_customer_user.manager');

        $role = $this->getManager()
            ->getRepository('OroCustomerBundle:CustomerUserRole')
            ->findOneBy(['role' => 'ROLE_FRONTEND_ADMINISTRATOR']);

        $customerUser = new CustomerUser();
        $customerUser
            ->setFirstName($email)
            ->setLastName($email)
            ->setEmail($email)
            ->addRole($role)
            ->setEnabled(true)
            ->setPlainPassword($email);

        if ($customer) {
            $customer->addUser($customerUser);
        }

        $userManager->updateUser($customerUser);

        $this->getManager()->persist($customerUser);
        $this->getManager()->flush();

        return $customerUser;
    }

    /**
     * @return null|Customer
     */
    protected function getDefaultCustomer()
    {
        /** @var CustomerRepository $repository */
        $repository = $this->getContainer()
            ->get('doctrine')
            ->getManager()
            ->getRepository(Customer::class);
        $defaultCustomer = $repository
            ->findOneByName('CustomerUser CustomerUser');

        return $defaultCustomer;
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager|EntityManager
     */
    protected function getManager()
    {
        return $manager = $this->getContainer()->get('doctrine')->getManager();
    }

    /**
     * @param string $name
     * @return null|CustomerGroup
     */
    protected function getGroup($name)
    {
        return $this->getManager()->getRepository(CustomerGroup::class)->findOneByName($name);
    }

    /**
     * @param int $ratingId
     * @return null|object|AbstractEnumValue
     */
    protected function getRating($ratingId)
    {
        $className = ExtendHelper::buildEnumValueClassName(Customer::INTERNAL_RATING_CODE);
        /** @var EnumValueRepository $enumRepo */
        $enumRepo = $this->getManager()->getRepository($className);
        $rating = $enumRepo->find($ratingId);

        return $rating;
    }

    /**
     * @param mixed $needle
     * @param Collection|mixed[] $haystack
     */
    protected function assertContainsById($needle, $haystack)
    {
        if ($haystack instanceof Collection) {
            $haystack = $haystack->toArray();
        }

        $haystack = array_map(
            function ($item) {
                /** @var mixed $item */
                return $item->getId();
            },
            $haystack
        );

        $this->assertContains($needle->getId(), $haystack);
    }

    /**
     * @param array $entities
     */
    protected function deleteEntities(array $entities)
    {
        $manager = $this->getManager();
        $manager->clear();
        foreach ($entities as $entity) {
            $entity = $manager->getRepository(get_class($entity))->find($entity->getId());
            $manager->remove($entity);
        }
        $manager->flush();
    }
}
