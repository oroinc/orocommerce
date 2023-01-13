<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;

class LoadPageSlugPrototypeData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array{?string,array{string,string}}
     *  [
     *      // Page reference => [...]
     *      string => [
     *          // Localization reference or null for system localization => slug prototype
     *          ?string => string
     *      ],
     *      // ...
     *  ]
     */
    public const SLUG_PROTOTYPES = [
        LoadPageData::PAGE_1 => [
            null => 'page-1',
            LoadLocalizationData::EN_CA_LOCALIZATION_CODE => 'page-1-en-ca',
        ],
        LoadPageData::PAGE_2 => [
            null => 'page-2',
        ],
    ];

    public function getDependencies(): array
    {
        return [
            LoadPageData::class,
            LoadLocalizationData::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        foreach (static::SLUG_PROTOTYPES as $pageReference => $slugPrototypes) {
            /** @var Page $page */
            $page = $this->getReference($pageReference);

            /**
             * @var string|null $localization
             * @var string $string
             */
            foreach ($slugPrototypes as $localization => $string) {
                /** @var ?Localization $localization */
                $localization = $localization ? $this->getReference($localization) : null;
                $slugPrototype = (new LocalizedFallbackValue())
                    ->setString($string)
                    ->setLocalization($localization);
                $this->setReference($string, $slugPrototype);
                $page->addSlugPrototype($slugPrototype);
            }
            $manager->persist($page);
        }

        $manager->flush();
    }
}
