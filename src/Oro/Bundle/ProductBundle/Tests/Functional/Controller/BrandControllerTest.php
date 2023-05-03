<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Controller;

use Oro\Bundle\EntityExtendBundle\EntityPropertyInfo;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadBrandData;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class BrandControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([LoadUserData::class, LoadBrandData::class]);
    }

    public function testGetChangedUrlsWhenSlugChanged()
    {
        $brand = $this->getFirstBrand();
        if (EntityPropertyInfo::methodExists($brand, 'setDefaultSlugPrototype')) {
            $brand->setDefaultSlugPrototype('old-default-slug');
        }

        $englishLocalization = $this->getContainer()->get('oro_locale.manager.localization')
            ->getDefaultLocalization(false);

        $englishSlugPrototype = new LocalizedFallbackValue();
        $englishSlugPrototype->setString('old-english-slug')->setLocalization($englishLocalization);

        $entityManager = $this->getContainer()->get('doctrine')->getManagerForClass(Brand::class);
        $brand->addSlugPrototype($englishSlugPrototype);

        $entityManager->persist($brand);
        $entityManager->flush();

        /** @var Localization $englishLocalization */
        $englishCALocalization = $this->getReference('en_CA');

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_product_brand_update', ['id' => $brand->getId()])
        );

        $form = $crawler->selectButton('Save')->form();
        $formValues = $form->getPhpValues();
        $formValues['oro_product_brand']['slugPrototypesWithRedirect'] = [
            'slugPrototypes' => [
                'values' => [
                    'default' => 'default-slug',
                    'localizations' => [
                        $englishLocalization->getId() => ['value' => 'english-slug'],
                        $englishCALocalization->getId() => ['value' => 'old-default-slug']
                    ]
                ]
            ]
        ];

        $this->client->request(
            'POST',
            $this->getUrl('oro_product_brand_get_changed_slugs', ['id' => $brand->getId()]),
            $formValues
        );

        $expectedData = [
            'Default Value' => ['before' => '/old-default-slug', 'after' => '/default-slug'],
            'English (United States)' => ['before' => '/old-english-slug','after' => '/english-slug']
        ];

        $response = $this->client->getResponse();
        $this->assertJsonStringEqualsJsonString(
            json_encode($expectedData, JSON_THROW_ON_ERROR),
            $response->getContent()
        );
    }

    private function getFirstBrand(): Brand
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository(Brand::class)
            ->findOneBy([]);
    }
}
