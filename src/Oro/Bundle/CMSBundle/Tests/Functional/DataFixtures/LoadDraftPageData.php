<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

class LoadDraftPageData extends AbstractFixture implements DependentFixtureInterface
{
    public const BASIC_PAGE_1 = 'basic.page.1';
    public const BASIC_PAGE_1_DRAFT_1 = 'basic.page.1.draft.1';
    public const BASIC_PAGE_1_DRAFT_2 = 'basic.page.1.draft.2';
    public const BASIC_PAGE_2 = 'basic.page.2';

    /**
     * @var array
     */
    private static $page = [
        self::BASIC_PAGE_1 => [],
        self::BASIC_PAGE_1_DRAFT_1 => [
            'draftSource' => self::BASIC_PAGE_1
        ],
        self::BASIC_PAGE_1_DRAFT_2 => [
            'draftSource' => self::BASIC_PAGE_1
        ],
        self::BASIC_PAGE_2 => [],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadOrganization::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $manager->createQuery('DELETE OroCMSBundle:Page')->execute(); // Remove all built-in pages before tests
        $organization = $this->getReference('organization');
        foreach (self::$page as $reference => $data) {
            $entity = new Page();
            $entity->addTitle((new LocalizedFallbackValue())->setString($reference));
            $entity->setContent($reference);
            $entity->setOrganization($organization);
            if (isset($data['draftSource'])) {
                $entity->setDraftSource($this->getReference($data['draftSource']));
                $entity->setDraftUuid(UUIDGenerator::v4());
            }

            $this->setReference($reference, $entity);
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
