<?php

namespace Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData as BaseFixture;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

class LoadCustomerUserData extends AbstractFixture implements DependentFixtureInterface
{
    const EMAIL = BaseFixture::EMAIL;
    const PASSWORD = BaseFixture::PASSWORD;
    const LEVEL_1_EMAIL = BaseFixture::LEVEL_1_EMAIL;

    /**
     * @var array
     */
    private static $customerReferences = [
        self::EMAIL,
        self::LEVEL_1_EMAIL
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::$customerReferences as $referenceName) {
            $this
                ->getReference($referenceName)
                ->setWebsite(
                    $this->getReference(LoadWebsiteData::WEBSITE1)
                );
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadWebsiteData::class,
            BaseFixture::class
        ];
    }
}
