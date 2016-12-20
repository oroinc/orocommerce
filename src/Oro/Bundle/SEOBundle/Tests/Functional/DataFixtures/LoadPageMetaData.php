<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;

class LoadPageMetaData extends AbstractFixture implements DependentFixtureInterface
{
    use SEOMetaDataFieldsTrait;

    const META_DESCRIPTIONS = 'metaDescriptions';
    const META_KEYWORDS = 'metaKeywords';

    /**
     * @var array
     */
    public static $metadata = [
        LoadPageData::PAGE_1 => [
            self::META_DESCRIPTIONS => self::META_DESCRIPTIONS,
            self::META_KEYWORDS => self::META_KEYWORDS,
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::$metadata as $entityReference => $metadataFields) {
            $entity = $this->getReference($entityReference);
            $this->loadLocalizedFallbackValues($manager, $entity, $metadataFields);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadPageData::class,
        ];
    }
}
