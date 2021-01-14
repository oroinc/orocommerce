<?php
declare(strict_types=1);

namespace Oro\Bundle\SEOBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\SEOBundle\Migrations\Schema\OroSEOBundleInstaller;

class LoadProductMetaData extends AbstractFixture implements DependentFixtureInterface
{
    use SEOMetaDataFieldsTrait;

    public const META_TITLES = 'metaTitles';
    public const META_DESCRIPTIONS = 'metaDescriptions';
    public const META_KEYWORDS = 'metaKeywords';

    public static array $metadata = [
        LoadProductData::PRODUCT_1 => [
            OroSEOBundleInstaller::METAINFORMATION_TITLES => self::META_TITLES,
            OroSEOBundleInstaller::METAINFORMATION_DESCRIPTIONS => self::META_DESCRIPTIONS,
            OroSEOBundleInstaller::METAINFORMATION_KEYWORDS => self::META_KEYWORDS,
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
            LoadProductData::class,
        ];
    }
}
