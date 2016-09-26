<?php

namespace Oro\Bundle\FrontendNavigationBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData;
use Oro\Bundle\FrontendNavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

class LoadMenuUpdateData extends AbstractFixture implements
    DependentFixtureInterface
{
    const MENU = 'user_menu';
    const ORGANIZATION = 'default_organization';
    const ACCOUNT = 'account.level_1';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts',
            'Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData',
            'Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->addReference(
            self::ORGANIZATION,
            $manager->getRepository('OroOrganizationBundle:Organization')->getFirst()
        );

        $updatesData = [
            [
                'ownershipType' => MenuUpdate::OWNERSHIP_ORGANIZATION,
                'ownerId' => $this->getReference(self::ORGANIZATION)->getId(),
                'key' => 'profile',
                'url' => '/profile',
            ],
            [
                'ownershipType' => MenuUpdate::OWNERSHIP_GLOBAL,
                'ownerId' => null,
                'key' => 'shipping_lists',
                'parentKey' => 'profile',
                'url' => '/shipping-lists',
            ],
            [
                'ownershipType' => MenuUpdate::OWNERSHIP_ACCOUNT_USER,
                'ownerId' => $this->getReference(LoadAccountUserData::EMAIL)->getId(),
                'key' => 'orders',
                'priority' => 5,
                'website' => $this->getReference(LoadWebsiteData::WEBSITE1),
            ],
            [
                'ownershipType' => MenuUpdate::OWNERSHIP_ACCOUNT,
                'ownerId' => $this->getReference(self::ACCOUNT)->getId(),
                'key' => 'quotes',
                'priority' => 5,
                'website' => $this->getReference(LoadWebsiteData::WEBSITE1),
            ],
        ];

        foreach ($updatesData as $updateData) {
            $update = new MenuUpdate();
            $update
                ->setOwnershipType($updateData['ownershipType'])
                ->setOwnerId($updateData['ownerId'])
                ->setMenu(self::MENU)
                ->setKey($updateData['key'])
            ;

            if (array_key_exists('parentKey', $updateData)) {
                $update->setParentKey($updateData['parentKey']);
            }

            if (array_key_exists('active', $updateData)) {
                $update->setActive($updateData['active']);
            }

            if (array_key_exists('priority', $updateData)) {
                $update->setPriority($updateData['priority']);
            }

            if (array_key_exists('website', $updateData)) {
                $update->setWebsite($updateData['website']);
            }

            $manager->persist($update);
        }

        $manager->flush();
    }
}
