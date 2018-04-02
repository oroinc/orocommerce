<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadBrandData;
use Oro\Bundle\SEOBundle\Migrations\Schema\OroSEOBundleInstaller;

class LoadBrandMetaData extends AbstractFixture implements DependentFixtureInterface
{
    use SEOMetaDataFieldsTrait;

    const META_TITLES = 'metaTitles';
    const META_DESCRIPTIONS = 'metaDescriptions';
    const META_KEYWORDS = 'metaKeywords';

    /**
     * @var array
     */
    public static $metadata = [
        LoadBrandData::BRAND_1 => [
            OroSEOBundleInstaller::METAINFORMATION_TITLES => self::META_TITLES,
            OroSEOBundleInstaller::METAINFORMATION_DESCRIPTIONS => self::META_DESCRIPTIONS,
            OroSEOBundleInstaller::METAINFORMATION_KEYWORDS => self::META_KEYWORDS,
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
            LoadBrandData::class,
        ];
    }
}
