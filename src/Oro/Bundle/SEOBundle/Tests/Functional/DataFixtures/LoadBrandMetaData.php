<?php

declare(strict_types=1);

namespace Oro\Bundle\SEOBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadBrandData;

class LoadBrandMetaData extends AbstractFixture implements DependentFixtureInterface
{
    use SEOMetaDataFieldsTrait;

    public const META_TITLES = 'metaTitles';
    public const META_DESCRIPTIONS = 'metaDescriptions';
    public const META_KEYWORDS = 'metaKeywords';

    public static array $metadata = [
        LoadBrandData::BRAND_1 => [
            'metaTitles' => self::META_TITLES,
            'metaDescriptions' => self::META_DESCRIPTIONS,
            'metaKeywords' => self::META_KEYWORDS,
        ]
    ];

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        foreach (self::$metadata as $entityReference => $metadataFields) {
            $entity = $this->getReference($entityReference);
            $this->loadLocalizedFallbackValues($manager, $entity, $metadataFields);
        }

        $manager->flush();
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadBrandData::class,
        ];
    }
}
