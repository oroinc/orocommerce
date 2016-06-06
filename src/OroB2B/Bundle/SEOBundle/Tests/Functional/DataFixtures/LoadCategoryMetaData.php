<?php

namespace OroB2B\Bundle\SEOBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;

class LoadCategoryMetaData extends AbstractFixture implements DependentFixtureInterface
{
    use SEOMetaDataFieldsTrait;

    const META_TITLES = 'categoryMetaTitles';
    const META_DESCRIPTIONS = 'categoryMetaDescriptions';
    const META_KEYWORDS = 'categoryMetaKeywords';

    /**
     * @var array
     */
    public static $metadata = [
        LoadCategoryData::FIRST_LEVEL => [
            self::META_TITLES => LoadCategoryData::FIRST_LEVEL,
            self::META_DESCRIPTIONS => self::META_DESCRIPTIONS,
            self::META_KEYWORDS => self::META_KEYWORDS,
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData',
        ];
    }
}
