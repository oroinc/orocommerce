<?php

namespace Oro\Bundle\MenuBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\MenuBundle\Entity\MenuItem;

class AddConditionForMyAccountMenu extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 20;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var MenuItem $item */
        $item = $manager->getRepository('OroMenuBundle:MenuItem')->findMenuItemByTitle('My Account');
        $item->setCondition('is_logged_in()');
        $manager->flush();
    }
}
