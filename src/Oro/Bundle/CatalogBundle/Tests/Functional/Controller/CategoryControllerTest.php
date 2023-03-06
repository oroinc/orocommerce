<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Controller;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Tests\Functional\CatalogTrait;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\EntityExtendBundle\EntityPropertyInfo;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CategoryControllerTest extends WebTestCase
{
    use CatalogTrait;

    private const DEFAULT_CATEGORY_TITLE = 'Category Title';
    private const DEFAULT_SUBCATEGORY_TITLE = 'Subcategory Title';
    private const DEFAULT_CATEGORY_SHORT_DESCRIPTION = 'Category Short Description';
    private const DEFAULT_CATEGORY_LONG_DESCRIPTION = 'Category Long Description';
    private const DEFAULT_CATEGORY_UNIT_CODE = 'set';
    private const DEFAULT_CATEGORY_UNIT_PRECISION = 5;
    private const UPDATED_DEFAULT_CATEGORY_TITLE = 'Updated Category Title';
    private const UPDATED_DEFAULT_SUBCATEGORY_TITLE = 'Updated Subcategory Title';
    private const UPDATED_DEFAULT_CATEGORY_SHORT_DESCRIPTION = 'Updated Category Short Description';
    private const UPDATED_DEFAULT_CATEGORY_LONG_DESCRIPTION = 'Updated Category Long Description';
    private const UPDATED_DEFAULT_CATEGORY_UNIT_CODE = 'item';
    private const UPDATED_DEFAULT_CATEGORY_UNIT_PRECISION = 3;
    private const LARGE_IMAGE_NAME = 'large_image.png';
    private const SMALL_IMAGE_NAME = 'small_image.png';
    private const LARGE_SVG_IMAGE_NAME = 'large_svg_image.svg';
    private const SMALL_SVG_IMAGE_NAME = 'small_svg_image.svg';

    /** @var Localization[] */
    private array $localizations;
    private Category $masterCatalog;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([
            LoadLocalizationData::class,
            LoadProductData::class,
            LoadCategoryData::class
        ]);

        $this->localizations = $this->getContainer()->get('doctrine')
            ->getRepository(Localization::class)
            ->findAll();

        $this->masterCatalog = $this->getRootCategory();
    }

    public function testGetChangedUrlsWhenSlugChanged()
    {
        /** @var Category $category */
        $category = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        if (EntityPropertyInfo::methodExists($category, 'setDefaultSlugPrototype')) {
            $category->setDefaultSlugPrototype('old-default-slug');
        }

        $englishLocalization = $this->getContainer()->get('oro_locale.manager.localization')
            ->getDefaultLocalization(false);

        $englishSlugPrototype = new LocalizedFallbackValue();
        $englishSlugPrototype->setString('old-english-slug')->setLocalization($englishLocalization);

        $entityManager = $this->getContainer()->get('doctrine')->getManagerForClass(Category::class);
        $category->addSlugPrototype($englishSlugPrototype);

        $entityManager->persist($category);
        $entityManager->flush();

        /** @var Localization $englishLocalization */
        $englishCALocalization = $this->getReference('en_CA');

        $crawler = $this->client->request('GET', $this->getUrl('oro_catalog_category_update', [
            'id' => $category->getId()
        ]));

        $form = $crawler->selectButton('Save')->form();
        $formValues = $form->getPhpValues();
        $formValues['oro_catalog_category']['slugPrototypesWithRedirect'] = [
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
            $this->getUrl('oro_catalog_category_get_changed_slugs', ['id' => $category->getId()]),
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

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_catalog_category_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertEquals('Categories', $crawler->filter('h1.oro-subtitle')->html());
        self::assertStringContainsString(
            'Please select a category on the left or create new one.',
            $crawler->filter('[data-role="content"] .tree-empty-content .no-data')->html()
        );
    }

    public function testCreateCategory(): int
    {
        $this->masterCatalog = $this->getRootCategory();

        return $this->assertCreate($this->masterCatalog->getId());
    }

    /**
     * @depends testCreateCategory
     */
    public function testCreateSubCategory(int $id): int
    {
        return $this->assertCreate($id, self::DEFAULT_SUBCATEGORY_TITLE);
    }

    /**
     * @depends testCreateCategory
     */
    public function testLocalizedValuesCategory(int $id)
    {
        $this->assertUpdateWithLocalizedValues($id);
    }

    /**
     * @depends testCreateSubCategory
     */
    public function testLocalizedValuesSubCategory(int $id)
    {
        $this->assertUpdateWithLocalizedValues($id, self::DEFAULT_SUBCATEGORY_TITLE);
    }

    /**
     * @depends testCreateCategory
     */
    public function testEditCategory(int $id): int
    {
        [$title, $shortDescription, $longDescription, $unitPrecision] = [
            self::DEFAULT_CATEGORY_TITLE,
            self::DEFAULT_CATEGORY_SHORT_DESCRIPTION,
            self::DEFAULT_CATEGORY_LONG_DESCRIPTION,
            [
                'code' => self::DEFAULT_CATEGORY_UNIT_CODE,
                'precision' => self::DEFAULT_CATEGORY_UNIT_PRECISION,
            ]
        ];

        [$newTitle, $newShortDescription, $newLongDescription, $newUnitPrecision] = [
            self::UPDATED_DEFAULT_CATEGORY_TITLE,
            self::UPDATED_DEFAULT_CATEGORY_SHORT_DESCRIPTION,
            self::UPDATED_DEFAULT_CATEGORY_LONG_DESCRIPTION,
            [
                'code' => self::UPDATED_DEFAULT_CATEGORY_UNIT_CODE,
                'precision' => self::UPDATED_DEFAULT_CATEGORY_UNIT_PRECISION,
            ]
        ];

        return $this->assertEdit(
            $id,
            $title,
            $shortDescription,
            $longDescription,
            $unitPrecision,
            $newTitle,
            $newShortDescription,
            $newLongDescription,
            $newUnitPrecision
        );
    }

    /**
     * @depends testCreateSubCategory
     */
    public function testEditSubCategory(int $id): int
    {
        [$title, $shortDescription, $longDescription, $unitPrecision] = [
            self::DEFAULT_SUBCATEGORY_TITLE,
            self::DEFAULT_CATEGORY_SHORT_DESCRIPTION,
            self::DEFAULT_CATEGORY_LONG_DESCRIPTION,
            [
                'code' => self::DEFAULT_CATEGORY_UNIT_CODE,
                'precision' => self::DEFAULT_CATEGORY_UNIT_PRECISION,
            ]
        ];

        [$newTitle, $newShortDescription, $newLongDescription, $newUnitPrecision] = [
            self::UPDATED_DEFAULT_CATEGORY_TITLE,
            self::UPDATED_DEFAULT_CATEGORY_SHORT_DESCRIPTION,
            self::UPDATED_DEFAULT_CATEGORY_LONG_DESCRIPTION,
            [
                'code' => self::UPDATED_DEFAULT_CATEGORY_UNIT_CODE,
                'precision' => self::UPDATED_DEFAULT_CATEGORY_UNIT_PRECISION,
            ]
        ];

        return $this->assertEdit(
            $id,
            $title,
            $shortDescription,
            $longDescription,
            $unitPrecision,
            $newTitle,
            $newShortDescription,
            $newLongDescription,
            $newUnitPrecision
        );
    }

    public function testGetChangedUrlsWhenNoSlugChanged()
    {
        $category = $this->getReference(LoadCategoryData::FIRST_LEVEL);

        $crawler = $this->client->request('GET', $this->getUrl('oro_catalog_category_update', [
            'id' => $category->getId()
        ]));

        $form = $crawler->selectButton('Save')->form();
        $formValues = $form->getPhpValues();

        $this->client->request(
            'POST',
            $this->getUrl('oro_catalog_category_get_changed_slugs', ['id' => $category->getId()]),
            $formValues
        );

        $response = $this->client->getResponse();
        $this->assertEquals('[]', $response->getContent());
    }

    public function testMove()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_catalog_category_move_form'),
            [
                'selected' => [
                    $this->getReference(LoadCategoryData::THIRD_LEVEL1)->getId()
                ],
                '_widgetContainer' => 'dialog',
            ],
            [],
            $this->generateWsseAuthHeader()
        );

        $form = $crawler->selectButton('Save')->form();
        $form['tree_move[target]'] = $this->getReference(LoadCategoryData::FIRST_LEVEL)->getId();

        $this->client->followRedirects(true);

        /** Change after BAP-1813 */
        $form->getFormNode()->setAttribute(
            'action',
            $form->getFormNode()->getAttribute('action') . '&_widgetContainer=dialog'
        );

        $this->client->submit($form);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var CategoryRepository $repository */
        $category = $this->findCategory(LoadCategoryData::THIRD_LEVEL1);
        $this->assertEquals(LoadCategoryData::FIRST_LEVEL, $category->getParentCategory()->getTitle()->getString());
    }

    public function testUploadSVGImages()
    {
        /** @var Category $category */
        $category = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_catalog_category_create', ['id' => $category->getId()])
        );

        $fileLocator = $this->getContainer()->get('file_locator');
        $smallImageName = self::SMALL_SVG_IMAGE_NAME;
        $smallImageFile = $fileLocator->locate(
            '@OroCatalogBundle/Tests/Functional/DataFixtures/files/' . $smallImageName
        );
        $largeImageName = self::LARGE_SVG_IMAGE_NAME;
        $largeImageFile = $fileLocator->locate(
            '@OroCatalogBundle/Tests/Functional/DataFixtures/files/' . $largeImageName
        );
        $smallImage = new UploadedFile($smallImageFile, $smallImageName, 'image/svg+xml');
        $largeImage = new UploadedFile($largeImageFile, $largeImageName, 'image/svg+xml');

        $title = 'Category with SVG images';
        $form = $crawler->selectButton('Save')->form();
        $form['oro_catalog_category[titles][values][default]'] = $title;
        $form['oro_catalog_category[smallImage][file]'] = $smallImage;
        $form['oro_catalog_category[largeImage][file]'] = $largeImage;
        $form['oro_catalog_category[inventoryThreshold][scalarValue]'] = 0;
        $form['oro_catalog_category[lowInventoryThreshold][scalarValue]'] = 0;
        $form['input_action'] = $crawler->selectButton('Save')->attr('data-action');

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        self::assertStringContainsString('Category has been saved', $html);
        self::assertStringContainsString($title, $html);
        self::assertStringContainsString(self::SMALL_SVG_IMAGE_NAME, $html);
        self::assertStringContainsString(self::LARGE_SVG_IMAGE_NAME, $html);
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $em = $this->getContainer()->get('doctrine')->getManager();
        $attachments = $em->getRepository(File::class)->findBy(['extension' => 'svg']);
        foreach ($attachments as $attachmentFile) {
            $url = $this->getContainer()->get('oro_attachment.manager')
                ->getFilteredImageUrl($attachmentFile, 'category_medium');
            $this->client->request(
                'GET',
                $url
            );
            $result = $this->client->getResponse();
            $this->assertResponseStatusCodeEquals($result, 200);
            $this->assertResponseContentTypeEquals($result, 'image/svg+xml');
        }
    }

    public function testValidationForLocalizedFallbackValues()
    {
        $rootId = $this->masterCatalog->getId();
        $crawler = $this->client->request('GET', $this->getUrl('oro_catalog_category_create', ['id' => $rootId]));
        $form = $crawler->selectButton('Save')->form();

        $bigStringValue = str_repeat('a', 256);
        $formValues = $form->getPhpValues();
        $formValues['oro_catalog_category']['inventoryThreshold']['scalarValue'] = 0;
        $formValues['oro_catalog_category'][LowInventoryProvider::LOW_INVENTORY_THRESHOLD_OPTION]['scalarValue'] = 0;
        $formValues['oro_catalog_category']['titles']['values']['default'] = $bigStringValue;
        $formValues['oro_catalog_category']['slugPrototypesWithRedirect']['slugPrototypes'] = [
            'values' => ['default' => $bigStringValue]
        ];

        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertEquals(
            2,
            $crawler->filterXPath(
                "//li[contains(text(),'This value is too long. It should have 255 characters or less.')]"
            )->count()
        );
    }

    private function assertCreate(
        int $parentId,
        string $title = self::DEFAULT_CATEGORY_TITLE,
        string $shortDescription = self::DEFAULT_CATEGORY_SHORT_DESCRIPTION,
        string $longDescription = self::DEFAULT_CATEGORY_LONG_DESCRIPTION,
        array $unitPrecision = [
            'code' => self::DEFAULT_CATEGORY_UNIT_CODE,
            'precision' => self::DEFAULT_CATEGORY_UNIT_PRECISION
        ]
    ): int {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_catalog_category_create', ['id' => $parentId])
        );

        $fileLocator = $this->getContainer()->get('file_locator');

        $smallImageName = self::SMALL_IMAGE_NAME;
        $smallImageFile = $fileLocator->locate(
            '@OroCatalogBundle/Tests/Functional/DataFixtures/files/' . $smallImageName
        );
        $largeImageName = self::LARGE_IMAGE_NAME;
        $largeImageFile = $fileLocator->locate(
            '@OroCatalogBundle/Tests/Functional/DataFixtures/files/' . $largeImageName
        );

        $smallImage = new UploadedFile($smallImageFile, $smallImageName);
        $largeImage = new UploadedFile($largeImageFile, $largeImageName);

        $form = $crawler->selectButton('Save')->form();
        $form['oro_catalog_category[titles][values][default]'] = $title;
        $form['oro_catalog_category[shortDescriptions][values][default]'] = $shortDescription;
        $form['oro_catalog_category[longDescriptions][values][default][wysiwyg]'] = $longDescription;
        $form['oro_catalog_category[smallImage][file]'] = $smallImage;
        $form['oro_catalog_category[largeImage][file]'] = $largeImage;
        $form['oro_catalog_category[inventoryThreshold][scalarValue]'] = 0;
        $form['oro_catalog_category[lowInventoryThreshold][scalarValue]'] = 0;
        $form['oro_catalog_category[defaultProductOptions][unitPrecision][unit]'] = $unitPrecision['code'];
        $form['oro_catalog_category[defaultProductOptions][unitPrecision][precision]'] = $unitPrecision['precision'];
        $form['input_action'] = $crawler->selectButton('Save')->attr('data-action');

        if ($parentId === $this->masterCatalog->getId()) {
            $appendProducts = $this->getProductBySku(LoadProductData::PRODUCT_1)->getId() . ', '
                . $this->getProductBySku(LoadProductData::PRODUCT_2)->getId();
            $form['oro_catalog_category[sortOrder]'] = json_encode([
                $this->getProductBySku(LoadProductData::PRODUCT_2)->getId() => ['categorySortOrder' => 0.2]
            ]);
        } else {
            $appendProducts = $this->getProductBySku(LoadProductData::PRODUCT_4)->getId();
        }

        $form['oro_catalog_category[appendProducts]'] = $appendProducts;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();
        self::assertStringContainsString('Category has been saved', $html);
        self::assertStringContainsString($title, $html);
        self::assertStringContainsString($shortDescription, $html);
        self::assertStringContainsString($longDescription, $html);
        self::assertStringContainsString($smallImage->getFilename(), $html);
        self::assertStringContainsString($largeImage->getFilename(), $html);
        if ($parentId === $this->masterCatalog->getId()) {
            self::assertStringContainsString('"categorySortOrder":"0.2"', $html);
        }
        $this->assertEquals($unitPrecision['code'], $crawler->filter('.unit option[selected]')->attr('value'));
        $this->assertEquals($unitPrecision['precision'], $crawler->filter('.precision')->attr('value'));

        return $this->getCategoryIdByUri($this->client->getRequest()->getRequestUri());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function assertEdit(
        int $id,
        string $title,
        string $shortDescription,
        string $longDescription,
        array $unitPrecision,
        string $newTitle,
        string $newShortDescription,
        string $newLongDescription,
        array $newUnitPrecision
    ): int {
        $crawler = $this->client->request('GET', $this->getUrl('oro_catalog_category_update', ['id' => $id]));
        $form = $crawler->selectButton('Save')->form();
        $formValues = $form->getValues();
        $html = $crawler->html();
        //Verified that actual values correspond with the ones that were set during Category creation
        self::assertStringContainsString('Add note', $html);
        self::assertStringContainsString(self::SMALL_IMAGE_NAME, $html);
        self::assertStringContainsString(self::LARGE_IMAGE_NAME, $html);
        $this->assertFormDefaultLocalized($formValues, $title, $shortDescription, $longDescription);
        $this->assertEquals($unitPrecision['code'], $crawler->filter('.unit option[selected]')->attr('value'));
        $this->assertEquals($unitPrecision['precision'], $crawler->filter('.precision')->attr('value'));

        $testProductOne = $this->getProductBySku(LoadProductData::PRODUCT_1);
        $testProductTwo = $this->getProductBySku(LoadProductData::PRODUCT_2);
        $testProductThree = $this->getProductBySku(LoadProductData::PRODUCT_3);
        $testProductFour = $this->getProductBySku(LoadProductData::PRODUCT_4);
        $appendProduct = $testProductThree;
        $sortOrder = [$testProductTwo->getId() => ['categorySortOrder' => 0.22]];

        if ($title === self::DEFAULT_SUBCATEGORY_TITLE) {
            $appendProduct = $testProductFour;
            $sortOrder = [$appendProduct->getId() => ['categorySortOrder' => 0.4]];
        }
        $crfToken = $this->getCsrfToken('category')->getValue();
        $params = [
            'input_action' => 'save_and_stay',
            'oro_catalog_category' => [
                '_token' => $crfToken,
                'sortOrder' => json_encode($sortOrder),
                'appendProducts' => $appendProduct->getId(),
                'removeProducts' => $testProductOne->getId()
            ]
        ];

        $params['oro_catalog_category']['titles']['values']['default'] = $newTitle;
        $params['oro_catalog_category']['shortDescriptions']['values']['default'] = $newShortDescription;
        $params['oro_catalog_category']['longDescriptions']['values']['default']['wysiwyg'] = $newLongDescription;
        $params['oro_catalog_category']['largeImage']['emptyFile'] = true;
        $params['oro_catalog_category']['inventoryThreshold']['scalarValue'] = 0;
        $params['oro_catalog_category'][LowInventoryProvider::LOW_INVENTORY_THRESHOLD_OPTION]['scalarValue'] = 0;
        $params['oro_catalog_category']['defaultProductOptions']['unitPrecision']['unit'] =
            $newUnitPrecision['code'];
        $params['oro_catalog_category']['defaultProductOptions']['unitPrecision']['precision'] =
            $newUnitPrecision['precision'];

        foreach ($this->localizations as $localization) {
            $locId = $localization->getId();

            $params['oro_catalog_category']['titles']['values']['localizations'][$locId]['value']
                = $localization->getLanguageCode() . $newTitle;
            $params['oro_catalog_category']['shortDescriptions']['values']['localizations'][$locId]['value']
                = $localization->getLanguageCode() . $newShortDescription;
            $params['oro_catalog_category']['longDescriptions']['values']['localizations'][$locId]['value']['wysiwyg']
                = $localization->getLanguageCode() . $newLongDescription;
        }
        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $params);
        $html = $crawler->html();
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('Category has been saved', $html);

        $form = $crawler->selectButton('Save')->form();
        $formValues = $form->getValues();
        //Verified that values correspond with the new ones that has been set after submit
        $this->assertFormDefaultLocalized($formValues, $newTitle, $newShortDescription, $newLongDescription);
        $this->assertLocalizedValues($formValues, $newTitle, $newShortDescription, $newLongDescription);
        $this->assertNull($this->getProductCategoryByProduct($testProductOne));
        self::assertStringNotContainsString(self::LARGE_IMAGE_NAME, $html);
        self::assertStringContainsString(self::SMALL_IMAGE_NAME, $html);
        $this->assertEquals($newUnitPrecision['code'], $crawler->filter('.unit option[selected]')->attr('value'));
        $this->assertEquals($newUnitPrecision['precision'], $crawler->filter('.precision')->attr('value'));

        if ($title === self::DEFAULT_CATEGORY_TITLE) {
            $productTwoCategory = $this->getProductCategoryByProduct($testProductTwo);
            $productThreeCategory = $this->getProductCategoryByProduct($testProductThree);
            $this->assertEquals(
                0.22,
                $this->getProductBySku(LoadProductData::PRODUCT_2)->getCategorySortOrder()
            );
            $this->assertEquals(
                null,
                $this->getProductBySku(LoadProductData::PRODUCT_3)->getCategorySortOrder()
            );

            $this->assertCategoryDefaultLocalized(
                $productThreeCategory,
                $newTitle,
                $newShortDescription,
                $newLongDescription
            );

            $this->assertCategoryDefaultLocalized(
                $productTwoCategory,
                $newTitle,
                $newShortDescription,
                $newLongDescription
            );
        }

        if ($title === self::DEFAULT_SUBCATEGORY_TITLE) {
            $productFourCategory = $this->getProductCategoryByProduct($testProductFour);
            $this->assertEquals(
                0.4,
                $this->getProductBySku(LoadProductData::PRODUCT_4)->getCategorySortOrder()
            );

            $this->assertCategoryDefaultLocalized(
                $productFourCategory,
                $newTitle,
                $newShortDescription,
                $newLongDescription
            );
        }

        return $id;
    }

    private function assertUpdateWithLocalizedValues(
        int $id,
        string $title = self::DEFAULT_CATEGORY_TITLE,
        string $shortDescription = self::DEFAULT_CATEGORY_SHORT_DESCRIPTION,
        string $longDescription = self::DEFAULT_CATEGORY_LONG_DESCRIPTION
    ): void {
        $crawler = $this->client->request('GET', $this->getUrl('oro_catalog_category_update', ['id' => $id]));
        $form = $crawler->selectButton('Save')->form();
        $formValues = $form->getValues();

        $this->assertEquals($title, $formValues['oro_catalog_category[titles][values][default]']);
        $this->assertEquals(
            $shortDescription,
            $formValues['oro_catalog_category[shortDescriptions][values][default]']
        );
        $this->assertEquals(
            $longDescription,
            $formValues['oro_catalog_category[longDescriptions][values][default][wysiwyg]']
        );

        if ($title === self::DEFAULT_CATEGORY_TITLE) {
            $testProductOne = $this->getProductBySku(LoadProductData::PRODUCT_1);
            $testProductTwo = $this->getProductBySku(LoadProductData::PRODUCT_2);

            $productOneCategory = $this->getProductCategoryByProduct($testProductOne);
            $productTwoCategory = $this->getProductCategoryByProduct($testProductTwo);

            $this->assertCategoryDefaultLocalized($productOneCategory, $title, $shortDescription, $longDescription);
            $this->assertCategoryDefaultLocalized($productTwoCategory, $title, $shortDescription, $longDescription);
        }

        if ($title === self::DEFAULT_SUBCATEGORY_TITLE) {
            $testProductFour = $this->getProductBySku(LoadProductData::PRODUCT_4);
            $productFourCategory = $this->getProductCategoryByProduct($testProductFour);
            $this->assertCategoryDefaultLocalized($productFourCategory, $title, $shortDescription, $longDescription);
        }
    }

    private function assertCategoryDefaultLocalized(
        Category $category,
        string $title,
        string $shortDescription,
        string $longDescription
    ): void {
        $this->assertEquals($title, $category->getDefaultTitle());
        $this->assertEquals($shortDescription, $category->getDefaultShortDescription());
        $this->assertEquals($longDescription, $category->getDefaultLongDescription());
    }

    private function assertFormDefaultLocalized(
        array $formValues,
        string $title,
        string $shortDescription,
        string $longDescription
    ): void {
        $this->assertEquals($title, $formValues['oro_catalog_category[titles][values][default]']);

        $this->assertEquals(
            $shortDescription,
            $formValues['oro_catalog_category[shortDescriptions][values][default]']
        );

        $this->assertEquals(
            $longDescription,
            $formValues['oro_catalog_category[longDescriptions][values][default][wysiwyg]']
        );
    }

    private function assertLocalizedValues(
        array $formValues,
        string $title,
        string $shortDescription,
        string $longDescription
    ): void {
        foreach ($this->localizations as $localization) {
            $this->assertEquals(
                $localization->getLanguageCode().$title,
                $formValues['oro_catalog_category[titles][values][localizations]['.$localization->getId().'][value]']
            );

            $locId = $localization->getId();

            $this->assertEquals(
                $localization->getLanguageCode().$shortDescription,
                $formValues['oro_catalog_category[shortDescriptions][values][localizations]['.$locId.'][value]']
            );

            $this->assertEquals(
                $localization->getLanguageCode().$longDescription,
                $formValues['oro_catalog_category[longDescriptions][values][localizations]['.$locId.'][value][wysiwyg]']
            );
        }
    }

    private function getCategoryIdByUri(string $uri): int
    {
        $router = $this->getContainer()->get('router');
        $parameters = $router->match($uri);

        $this->assertArrayHasKey('id', $parameters);

        return $parameters['id'];
    }

    private function getProductBySku(string $sku): Product
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository(Product::class)
            ->findOneBy(['sku' => $sku]);
    }

    private function getProductCategoryByProduct(Product $product): ?Category
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository(Category::class)
            ->findOneByProduct($product);
    }
}
