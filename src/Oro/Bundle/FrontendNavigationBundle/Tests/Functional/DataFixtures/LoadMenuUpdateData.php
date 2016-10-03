<?php

namespace Oro\Bundle\FrontendNavigationBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\FrontendNavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\FrontendNavigationBundle\Provider\AccountOwnershipProvider;
use Oro\Bundle\NavigationBundle\Menu\Provider\GlobalOwnershipProvider;
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
                'ownershipType' => GlobalOwnershipProvider::TYPE,
                'ownerId' => $this->getReference(self::ORGANIZATION)->getId(),
                'key' => 'profile',
                'url' => '/profile',
            ],
            [
                'ownershipType' => AccountOwnershipProvider::TYPE,
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

            $manager->persist($update);
        }

        $manager->flush();
    }
}
