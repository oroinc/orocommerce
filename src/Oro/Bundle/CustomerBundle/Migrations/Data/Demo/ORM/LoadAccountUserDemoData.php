<?php

namespace Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Entity\AccountUserRole;

class LoadAccountUserDemoData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    const ACCOUNT_USERS_REFERENCE_PREFIX = 'account_user_demo_data_';

    /** @var ContainerInterface */
    protected $container;

    /** @var array */
    public static $accountUsersReferencesNames = [];

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
        return [LoadAccountDemoData::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var Website $website */
        $website = $manager->getRepository(Website::class)->findOneBy(['default' => true]);

        /** @var \Oro\Bundle\CustomerBundle\Entity\AccountUserManager $userManager */
        $userManager = $this->container->get('oro_account_user.manager');

        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroCustomerBundle/Migrations/Data/Demo/ORM/data/account-users.csv');
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

            $account = $this->getReference(LoadAccountDemoData::ACCOUNT_REFERENCE_PREFIX . $row['account']);
            if (!$account) {
                continue;
            }

            // create/get account user role
            $roleLabel = $row['role'];
            if (!array_key_exists($roleLabel, $roles)) {
                $roles[$roleLabel] = $this->getAccountUserRole($roleLabel);
            }
            $role = $roles[$roleLabel];

            // create account user
            /** @var AccountUser $accountUser */
            $accountUser = $userManager->createUser();
            $accountUser
                ->setWebsite($website)
                ->setUsername($row['email'])
                ->setEmail($row['email'])
                ->setFirstName($row['firstName'])
                ->setLastName($row['lastName'])
                ->setPlainPassword($row['email'] . '1Q')
                ->setAccount($account)
                ->setOwner($account->getOwner())
                ->setEnabled(true)
                ->setOrganization($organization)
                ->addOrganization($organization)
                ->setLoginCount(0)
                ->addRole($role);

            $userManager->updateUser($accountUser, false);

            $referenceName = self::ACCOUNT_USERS_REFERENCE_PREFIX . $row['email'];
            $this->addReference($referenceName, $accountUser);
            self::$accountUsersReferencesNames[] = $referenceName;
        }

        fclose($handler);
        $storageManager->flush();
    }

    /**
     * @param string $roleLabel
     * @return AccountUserRole
     */
    protected function getAccountUserRole($roleLabel)
    {
        return $this->container->get('doctrine')
            ->getManagerForClass('OroCustomerBundle:AccountUserRole')
            ->getRepository('OroCustomerBundle:AccountUserRole')
            ->findOneBy(['label' => $roleLabel]);
    }
}
