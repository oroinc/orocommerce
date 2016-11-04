<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;

class LoadCategoryMetaData extends AbstractFixture implements DependentFixtureInterface
{
    use SEOMetaDataFieldsTrait;

    const META_DESCRIPTIONS = 'metaDescriptions';
    const META_KEYWORDS = 'metaKeywords';

    /**
     * @var array
     */
    public static $metadata = [
        LoadCategoryData::FIRST_LEVEL => [
            self::META_DESCRIPTIONS => self::META_DESCRIPTIONS,
            self::META_KEYWORDS => self::META_KEYWORDS,
        ],
        LoadCategoryData::SECOND_LEVEL1 => [
            self::META_DESCRIPTIONS => 'defaultMetaDescription',
            self::META_KEYWORDS => 'defaultMetaKeywords',
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
            'Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData',
        ];
    }
}
