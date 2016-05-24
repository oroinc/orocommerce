<?php

namespace OroB2B\Bundle\SEOBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;
use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;

class LoadPageMetaData extends AbstractFixture implements DependentFixtureInterface
{
    use SEOMetaDataFieldsTrait;

    const META_TITLES = 'metaTitles';
    const META_DESCRIPTIONS = 'metaDescriptions';
    const META_KEYWORDS = 'metaKeywords';

    /**
     * @var array
     */
    public static $metadata = [
        LoadPageData::PAGE_1 => [
            self::META_TITLES => LoadPageData::PAGE_1 . self::META_TITLES,
            self::META_DESCRIPTIONS => LoadPageData::PAGE_1 . self::META_DESCRIPTIONS,
            self::META_KEYWORDS => LoadPageData::PAGE_1 . self::META_KEYWORDS,
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData',
        ];
    }
}
