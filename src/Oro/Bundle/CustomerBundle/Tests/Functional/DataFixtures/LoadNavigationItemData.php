<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\NavigationItem;

class LoadNavigationItemData extends AbstractFixture implements DependentFixtureInterface
{
    const ITEM_1 = 'oro_customer_bundle.item_1';
    const ITEM_2 = 'oro_customer_bundle.item_2';
    const ITEM_3 = 'oro_customer_bundle.item_3';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var CustomerUser $user */
        $user = $this->getReference(LoadCustomerUserData::LEVEL_1_EMAIL);

        $item1 = new NavigationItem();
        $item1->setUser($user);
        $item1->setType('frontend_pinbar');
        $item1->setUrl('test url 1');
        $item1->setTitle('test title 1');
        $item1->setPosition(1);
        $item1->setOrganization($user->getOrganization());
        $this->addReference(self::ITEM_1, $item1);
        $manager->persist($item1);

        $item2 = new NavigationItem();
        $item2->setUser($user);
        $item2->setType('frontend_pinbar');
        $item2->setUrl('test url 2');
        $item2->setTitle('test title 2');
        $item2->setPosition(2);
        $item2->setOrganization($user->getOrganization());
        $this->addReference(self::ITEM_2, $item2);
        $manager->persist($item2);

        $item3 = new NavigationItem();
        $item3->setUser($user);
        $item3->setType('frontend_pinbar');
        $item3->setUrl('test url 3');
        $item3->setTitle('test title 3');
        $item3->setPosition(3);
        $item3->setOrganization($user->getOrganization());
        $this->addReference(self::ITEM_3, $item3);
        $manager->persist($item3);

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadCustomerUserData::class];
    }
}
