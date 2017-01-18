<?php

namespace Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;

class LoadCustomerUserDemoData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    const ACCOUNT_USERS_REFERENCE_PREFIX = 'customer_user_demo_data_';

    /** @var ContainerInterface */
    protected $container;

    /** @var array */
    public static $customerUsersReferencesNames = [];

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /** @return array */
    public function getDependencies()
    {
        return [LoadCustomerDemoData::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var Website $website */
        $website = $manager->getRepository(Website::class)->findOneBy(['default' => true]);

        /** @var \Oro\Bundle\CustomerBundle\Entity\CustomerUserManager $userManager */
        $userManager = $this->container->get('oro_customer_user.manager');

        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroCustomerBundle/Migrations/Data/Demo/ORM/data/customer-users.csv');
        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');

        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->findOneBy([]);

        $storageManager = $userManager->getStorageManager();

        $roles = [];

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            $customer = $this->getReference(LoadCustomerDemoData::ACCOUNT_REFERENCE_PREFIX . $row['customer']);
            if (!$customer) {
                continue;
            }

            // create/get customer user role
            $roleLabel = $row['role'];
            if (!array_key_exists($roleLabel, $roles)) {
                $roles[$roleLabel] = $this->getCustomerUserRole($roleLabel);
            }
            $role = $roles[$roleLabel];

            // create customer user
            /** @var CustomerUser $customerUser */
            $customerUser = $userManager->createUser();
            $customerUser
                ->setWebsite($website)
                ->setUsername($row['email'])
                ->setEmail($row['email'])
                ->setFirstName($row['firstName'])
                ->setLastName($row['lastName'])
                ->setPlainPassword($row['email'])
                ->setCustomer($customer)
                ->setOwner($customer->getOwner())
                ->setEnabled(true)
                ->setOrganization($organization)
                ->addOrganization($organization)
                ->setLoginCount(0)
                ->addRole($role);

            $userManager->updateUser($customerUser, false);

            $referenceName = self::ACCOUNT_USERS_REFERENCE_PREFIX . $row['email'];
            $this->addReference($referenceName, $customerUser);
            self::$customerUsersReferencesNames[] = $referenceName;
        }

        fclose($handler);
        $storageManager->flush();
    }

    /**
     * @param string $roleLabel
     * @return CustomerUserRole
     */
    protected function getCustomerUserRole($roleLabel)
    {
        return $this->container->get('doctrine')
            ->getManagerForClass('OroCustomerBundle:CustomerUserRole')
            ->getRepository('OroCustomerBundle:CustomerUserRole')
            ->findOneBy(['label' => $roleLabel]);
    }
}
