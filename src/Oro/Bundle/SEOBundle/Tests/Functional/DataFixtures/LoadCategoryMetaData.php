<?php
declare(strict_types=1);

namespace Oro\Bundle\SEOBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;

class LoadCategoryMetaData extends AbstractFixture implements DependentFixtureInterface
{
    use SEOMetaDataFieldsTrait;

    public const META_DESCRIPTIONS = 'metaDescriptions';
    public const META_KEYWORDS = 'metaKeywords';

    public static array $metadata = [
        LoadCategoryData::FIRST_LEVEL => [
            self::META_DESCRIPTIONS => self::META_DESCRIPTIONS,
            self::META_KEYWORDS => self::META_KEYWORDS,
        ],
        LoadCategoryData::SECOND_LEVEL1 => [
            self::META_DESCRIPTIONS => 'defaultMetaDescription',
            self::META_KEYWORDS => 'defaultMetaKeywords',
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
            LoadCategoryProductData::class,
        ];
    }
}
