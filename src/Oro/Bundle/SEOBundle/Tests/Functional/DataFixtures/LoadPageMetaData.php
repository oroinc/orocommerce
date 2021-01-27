<?php
declare(strict_types=1);

namespace Oro\Bundle\SEOBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;

class LoadPageMetaData extends AbstractFixture implements DependentFixtureInterface
{
    use SEOMetaDataFieldsTrait;

    public const META_TITLES = 'metaTitles';
    public const META_DESCRIPTIONS = 'metaDescriptions';
    public const META_KEYWORDS = 'metaKeywords';

    public static array $metadata = [
        LoadPageData::PAGE_1 => [
            self::META_TITLES => self::META_TITLES,
            self::META_DESCRIPTIONS => self::META_DESCRIPTIONS,
            self::META_KEYWORDS => self::META_KEYWORDS,
        ]
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::$metadata as $entityReference => $metadataFields) {
            $entity = $this->getReference($entityReference);
            $this->loadLocalizedFallbackValues($manager, $entity, $metadataFields);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            LoadPageData::class,
        ];
    }
}
