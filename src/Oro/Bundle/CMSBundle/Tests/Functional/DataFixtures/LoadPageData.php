<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

class LoadPageData extends AbstractFixture implements DependentFixtureInterface
{
    public const PAGE_1 = 'page.1';
    public const PAGE_2 = 'page.2';
    public const PAGE_3 = 'page.3';

    protected static array $page = [
        self::PAGE_1 => [],
        self::PAGE_2 => [],
        self::PAGE_3 => [],
    ];

    public function load(ObjectManager $manager): void
    {
        $manager->createQuery('DELETE OroCMSBundle:Page')->execute(); // remove all built-in pages before tests
        foreach (self::$page as $menuItemReference => $data) {
            /** @var Organization $organization */
            $organization = $this->getReference(LoadOrganization::ORGANIZATION);
            $entity = (new Page())
                ->addTitle((new LocalizedFallbackValue())->setString($menuItemReference))
                ->setContent($menuItemReference)
                ->setOrganization($organization);

            $this->setReference($menuItemReference, $entity);
            $manager->persist($entity);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [LoadOrganization::class];
    }
}
