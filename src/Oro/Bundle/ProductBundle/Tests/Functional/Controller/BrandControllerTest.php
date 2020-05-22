<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Controller;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadBrandData;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Form;

class BrandControllerTest extends WebTestCase
{
    /**
     * @var Localization[]
     */
    protected $localizations;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures(
            [
                LoadUserData::class,
                LoadBrandData::class
            ]
        );

        $this->localizations = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroLocaleBundle:Localization')
            ->findAll();
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_brand_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertEquals('Product Brands', $crawler->filter('h1.oro-subtitle')->html());
        static::assertStringContainsString('brand-grid', $crawler->html());
    }

    public function testCreateBrand()
    {
        /** @var string $name */
        $name = LoadBrandData::BRAND_1_DEFAULT_NAME;
        /** @var string $description */
        $description = LoadBrandData::BRAND_1_DEFAULT_DESCRIPTION;
        /** @var string $shortDescription */
        $shortDescription = LoadBrandData::BRAND_1_DEFAULT_SHORT_DESCRIPTION;

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_product_brand_create')
        );

        /** @var Form $form */
        $form = $crawler->selectButton('Save')->form();
        $form['oro_product_brand[names][values][default]'] = $name;
        $form['oro_product_brand[descriptions][values][default][wysiwyg]'] = $description;
        $form['oro_product_brand[shortDescriptions][values][default]'] = $shortDescription;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();
        static::assertStringContainsString('Brand has been saved', $html);
    }

    public function testUpdate()
    {
        $brand = $this->getFirstBrand();

        $nameDefaultNew = 'name updated';
        $descriptionDefaultNew = 'description updated';
        $shortDescriptionDefaultNew = 'short description updated';

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_product_brand_update', ['id' => $brand->getId()])
        );

        $form = $crawler->selectButton('Save')->form();
        $form['oro_product_brand[names][values][default]'] = $nameDefaultNew;
        $form['oro_product_brand[descriptions][values][default][wysiwyg]'] = $descriptionDefaultNew;
        $form['oro_product_brand[shortDescriptions][values][default]'] = $shortDescriptionDefaultNew;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertStringContainsString("Brand has been saved", $result->getContent());
        $this->assertEquals(
            $nameDefaultNew,
            $crawler->filter('input[name="oro_product_brand[names][values][default]"]')->extract(['value'])[0]
        );
        $this->assertEquals(
            $descriptionDefaultNew,
            $crawler->filter('textarea[name="oro_product_brand[descriptions][values][default][wysiwyg]"]')->html()
        );
        $this->assertEquals(
            $shortDescriptionDefaultNew,
            $crawler->filter('textarea[name="oro_product_brand[shortDescriptions][values][default]"]')->html()
        );
    }

    public function testGetChangedUrlsWhenSlugChanged()
    {
        /** @var Brand $brand */
        $brand = $this->getFirstBrand();
        if (method_exists($brand, 'setDefaultSlugPrototype')) {
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
        $this->assertJsonStringEqualsJsonString(json_encode($expectedData), $response->getContent());
    }

    /**
     * @return Brand
     */
    private function getFirstBrand()
    {
        return $this
            ->getContainer()
            ->get('doctrine')
            ->getRepository('OroProductBundle:Brand')
            ->findOneBy([]);
    }
}
