<?php

namespace Oro\Bundle\AccountBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class LoadScopeAccountDemoData extends AbstractFixture implements FixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var \Oro\Bundle\AccountBundle\Entity\Account $account */
        $accounts = $manager->getRepository('OroAccountBundle:Account')->findAll();
        foreach ($accounts as $account) {
            $scope = new Scope();
            $scope
                ->setAccount($account);
            $this->addReference($account->getName(), $scope);
            $manager->persist($scope);
        }

        $manager->flush();
    }
}
