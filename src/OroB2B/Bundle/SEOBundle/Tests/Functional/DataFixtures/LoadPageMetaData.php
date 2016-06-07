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

    const META_TITLES = 'pageMetaTitles';
    const META_DESCRIPTIONS = 'pageMetaDescriptions';
    const META_KEYWORDS = 'pageMetaKeywords';

    /**
     * @var array
     */
    public static $metadata = [
        LoadPageData::PAGE_1 => [
            self::META_TITLES => LoadPageData::PAGE_1,
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
            'OroB2B\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData',
        ];
    }
}
