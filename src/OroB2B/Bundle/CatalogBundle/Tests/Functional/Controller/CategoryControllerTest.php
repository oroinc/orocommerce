<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Form;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\ProductBundle\Entity\Product;

/**
 * @dbIsolation
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class CategoryControllerTest extends WebTestCase
{
    const DEFAULT_CATEGORY_TITLE = 'Category Title';
    const DEFAULT_SUBCATEGORY_TITLE = 'Subcategory Title';
    const DEFAULT_CATEGORY_SHORT_DESCRIPTION = 'Category Short Description';
    const DEFAULT_CATEGORY_LONG_DESCRIPTION = 'Category Long Description';
    const UPDATED_DEFAULT_CATEGORY_TITLE = 'Updated Category Title';
    const UPDATED_DEFAULT_SUBCATEGORY_TITLE = 'Updated Subcategory Title';
    const UPDATED_DEFAULT_CATEGORY_SHORT_DESCRIPTION = 'Updated Category Short Description';
    const UPDATED_DEFAULT_CATEGORY_LONG_DESCRIPTION = 'Updated Category Long Description';
    const LARGE_IMAGE_NAME = 'large_image.png';
    const SMALL_IMAGE_NAME = 'small_image.png';

    /**
     * @var Localization[]
     */
    protected $localizations;

    /**
     * @var Category
     */
    protected $masterCatalog;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            'Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData',
            'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData'
        ]);
        $this->localizations = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroLocaleBundle:Localization')
            ->findAll();
        $this->masterCatalog = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BCatalogBundle:Category')
            ->getMasterCatalogRoot();
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_catalog_category_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertEquals('Categories', $crawler->filter('h1.oro-subtitle')->html());
        $this->assertContains(
            'Please select a category on the left or create new one.',
            $crawler->filter('.content .text-center')->html()
        );
    }

    /**
     * @return int
     */
    public function testCreateCategory()
    {
        $this->getContainer()->get('doctrine')->getRepository('OroB2BCatalogBundle:Category')->getMasterCatalogRoot();

        return $this->assertCreate($this->masterCatalog->getId());
    }

    /**
     * @depends testCreateCategory
     *
     * @param int $id
     *
     * @return int
     */
    public function testCreateSubCategory($id)
    {
        return $this->assertCreate($id, self::DEFAULT_SUBCATEGORY_TITLE);
    }

    /**
     * @depends testCreateCategory
     *
     * @param int $id
     *
     */
    public function testLocalizedValuesCategory($id)
    {
        $this->assertUpdateWithLocalizedValues($id);
    }

    /**
     * @depends testCreateSubCategory
     *
     * @param int $id
     *
     */
    public function testLocalizedValuesSubCategory($id)
    {
        $this->assertUpdateWithLocalizedValues($id, self::DEFAULT_SUBCATEGORY_TITLE);
    }

    /**
     * @depends testCreateCategory
     *
     * @param int $id
     *
     * @return int
     */
    public function testEditCategory($id)
    {
        list($title, $shortDescription, $longDescription) = [
            self::DEFAULT_CATEGORY_TITLE,
            self::DEFAULT_CATEGORY_SHORT_DESCRIPTION,
            self::DEFAULT_CATEGORY_LONG_DESCRIPTION
        ];

        list($newTitle, $newShortDescription, $newLongDescription) = [
            self::UPDATED_DEFAULT_CATEGORY_TITLE,
            self::UPDATED_DEFAULT_CATEGORY_SHORT_DESCRIPTION,
            self::UPDATED_DEFAULT_CATEGORY_LONG_DESCRIPTION
        ];

        return $this->assertEdit(
            $id,
            $title,
            $shortDescription,
            $longDescription,
            $newTitle,
            $newShortDescription,
            $newLongDescription
        );
    }

    /**
     * @depends testCreateSubCategory
     *
     * @param int $id
     *
     * @return int
     */
    public function testEditSubCategory($id)
    {
        list($title, $shortDescription, $longDescription) = [
            self::DEFAULT_SUBCATEGORY_TITLE,
            self::DEFAULT_CATEGORY_SHORT_DESCRIPTION,
            self::DEFAULT_CATEGORY_LONG_DESCRIPTION
        ];

        list($newTitle, $newShortDescription, $newLongDescription) = [
            self::UPDATED_DEFAULT_CATEGORY_TITLE,
            self::UPDATED_DEFAULT_CATEGORY_SHORT_DESCRIPTION,
            self::UPDATED_DEFAULT_CATEGORY_LONG_DESCRIPTION
        ];

        return $this->assertEdit(
            $id,
            $title,
            $shortDescription,
            $longDescription,
            $newTitle,
            $newShortDescription,
            $newLongDescription
        );
    }

    /**
     * @depends testEditCategory
     *
     * @param int $id
     */
    public function testDelete($id)
    {
        $this->client->request(
            'DELETE',
            $this->getUrl('orob2b_api_delete_category', ['id' => $id]),
            [],
            [],
            $this->generateWsseAuthHeader()
        );

        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $this->client->request('GET', $this->getUrl('orob2b_catalog_category_update', ['id' => $id]));

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 404);
    }


    public function testDeleteRoot()
    {
        $this->client->request(
            'DELETE',
            $this->getUrl('orob2b_api_delete_category', ['id' => $this->masterCatalog->getId()]),
            [],
            [],
            $this->generateWsseAuthHeader()
        );

        $result = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($result, 500);
    }

    /**
     * @param int    $parentId
     * @param string $title
     * @param string $shortDescription
     * @param string $longDescription
     *
     * @return int
     */
    protected function assertCreate(
        $parentId,
        $title = self::DEFAULT_CATEGORY_TITLE,
        $shortDescription = self::DEFAULT_CATEGORY_SHORT_DESCRIPTION,
        $longDescription = self::DEFAULT_CATEGORY_LONG_DESCRIPTION
    ) {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_catalog_category_create', ['id' => $parentId])
        );

        $fileLocator = $this->getContainer()->get('file_locator');

        $smallImageName = self::SMALL_IMAGE_NAME;
        $smallImageFile = $fileLocator->locate(
            '@OroB2BCatalogBundle/Tests/Functional/DataFixtures/files/' . $smallImageName
        );
        $largeImageName = self::LARGE_IMAGE_NAME;
        $largeImageFile = $fileLocator->locate(
            '@OroB2BCatalogBundle/Tests/Functional/DataFixtures/files/' . $largeImageName
        );

        $smallImage = new UploadedFile($smallImageFile, $smallImageName);
        $largeImage = new UploadedFile($largeImageFile, $largeImageName);

        /** @var Form $form */
        $form = $crawler->selectButton('Save')->form();
        $form['orob2b_catalog_category[titles][values][default]'] = $title;
        $form['orob2b_catalog_category[shortDescriptions][values][default]'] = $shortDescription;
        $form['orob2b_catalog_category[longDescriptions][values][default]'] = $longDescription;
        $form['orob2b_catalog_category[smallImage][file]'] = $smallImage;
        $form['orob2b_catalog_category[largeImage][file]'] = $largeImage;

        if ($parentId === $this->masterCatalog->getId()) {
            $appendProducts = $this->getProductBySku(LoadProductData::PRODUCT_1)->getId() . ', '
                . $this->getProductBySku(LoadProductData::PRODUCT_2)->getId();
        } else {
            $appendProducts = $this->getProductBySku(LoadProductData::PRODUCT_4)->getId();
        }

        $form['orob2b_catalog_category[appendProducts]'] = $appendProducts;
        $form->setValues(['input_action' => 'save_and_stay']);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();
        $this->assertContains('Category has been saved', $html);
        $this->assertContains($title, $html);
        $this->assertContains($shortDescription, $html);
        $this->assertContains($longDescription, $html);
        $this->assertContains($smallImage->getFilename(), $html);
        $this->assertContains($largeImage->getFilename(), $html);

        return $this->getCategoryIdByUri($this->client->getRequest()->getRequestUri());
    }

    /**
     * @param int    $id
     * @param string $title
     * @param string $shortDescription
     * @param string $longDescription
     * @param string $newTitle
     * @param string $newShortDescription
     * @param string $newLongDescription
     *
     * @return int
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function assertEdit(
        $id,
        $title,
        $shortDescription,
        $longDescription,
        $newTitle,
        $newShortDescription,
        $newLongDescription
    ) {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_catalog_category_update', ['id' => $id]));
        $form = $crawler->selectButton('Save')->form();
        $formValues = $form->getValues();
        $html = $crawler->html();
        //Verified that actual values correspond with the ones that were set during Category creation
        $this->assertContains('Add note', $html);
        $this->assertContains(self::SMALL_IMAGE_NAME, $html);
        $this->assertContains(self::LARGE_IMAGE_NAME, $html);
        $this->assertFormDefaultLocalized($formValues, $title, $shortDescription, $longDescription);

        $testProductOne = $this->getProductBySku(LoadProductData::PRODUCT_1);
        $testProductTwo = $this->getProductBySku(LoadProductData::PRODUCT_2);
        $testProductThree = $this->getProductBySku(LoadProductData::PRODUCT_3);
        $testProductFour = $this->getProductBySku(LoadProductData::PRODUCT_4);
        $appendProduct = $testProductThree;

        if ($title === self::DEFAULT_SUBCATEGORY_TITLE) {
            $appendProduct = $testProductFour;
        };
        $crfToken = $this->getContainer()->get('security.csrf.token_manager')->getToken('category');
        $parameters = [
            'input_action' => 'save_and_stay',
            'orob2b_catalog_category' => [
                '_token' => $crfToken,
                'appendProducts' => $appendProduct->getId(),
                'removeProducts' => $testProductOne->getId()
            ]
        ];

        $parameters['orob2b_catalog_category']['titles']['values']['default'] = $newTitle;
        $parameters['orob2b_catalog_category']['shortDescriptions']['values']['default'] = $newShortDescription;
        $parameters['orob2b_catalog_category']['longDescriptions']['values']['default'] = $newLongDescription;
        $parameters['orob2b_catalog_category']['largeImage']['emptyFile'] = true;

        $parentCategory = $crawler->filter('[name = "orob2b_catalog_category[parentCategory]"]')->attr('value');
        $parameters['orob2b_catalog_category']['parentCategory'] = $parentCategory;

        foreach ($this->localizations as $localization) {
            $parameters['orob2b_catalog_category']['titles']['values']['localizations'][$localization->getId()]['value']
                = $localization->getLanguageCode() . $newTitle;
            $parameters['orob2b_catalog_category']['shortDescriptions']['values']['localizations'][$localization->getId()]['value']
                = $localization->getLanguageCode() . $newShortDescription;
            $parameters['orob2b_catalog_category']['longDescriptions']['values']['localizations'][$localization->getId()]['value']
                = $localization->getLanguageCode() . $newLongDescription;
        }
        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $parameters);
        $html = $crawler->html();
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Category has been saved', $html);

        $form = $crawler->selectButton('Save')->form();
        $formValues = $form->getValues();
        //Verified that values correspond with the new ones that has been set after submit
        $this->assertFormDefaultLocalized($formValues, $newTitle, $newShortDescription, $newLongDescription);
        $this->assertLocalizedValues($formValues, $newTitle, $newShortDescription, $newLongDescription);
        $this->assertNull($this->getProductCategoryByProduct($testProductOne));
        $this->assertNotContains(self::LARGE_IMAGE_NAME, $html);
        $this->assertContains(self::SMALL_IMAGE_NAME, $html);

        if ($title === self::DEFAULT_CATEGORY_TITLE) {
            $productTwoCategory = $this->getProductCategoryByProduct($testProductTwo);
            $productThreeCategory = $this->getProductCategoryByProduct($testProductThree);

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

            $this->assertCategoryDefaultLocalized(
                $productFourCategory,
                $newTitle,
                $newShortDescription,
                $newLongDescription
            );
        }

        return $id;
    }

    /**
     * @param int    $id
     * @param string $title
     * @param string $shortDescription
     * @param string $longDescription
     */
    protected function assertUpdateWithLocalizedValues(
        $id,
        $title = self::DEFAULT_CATEGORY_TITLE,
        $shortDescription = self::DEFAULT_CATEGORY_SHORT_DESCRIPTION,
        $longDescription = self::DEFAULT_CATEGORY_LONG_DESCRIPTION
    ) {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_catalog_category_update', ['id' => $id]));
        $form = $crawler->selectButton('Save')->form();
        $formValues = $form->getValues();

        $this->assertEquals($title, $formValues['orob2b_catalog_category[titles][values][default]']);
        $this->assertEquals(
            $shortDescription,
            $formValues['orob2b_catalog_category[shortDescriptions][values][default]']
        );
        $this->assertEquals(
            $longDescription,
            $formValues['orob2b_catalog_category[longDescriptions][values][default]']
        );

        if ($title === self::DEFAULT_CATEGORY_TITLE) {
            $testProductOne = $this->getProductBySku(LoadProductData::PRODUCT_1);
            $testProductTwo = $this->getProductBySku(LoadProductData::PRODUCT_2);

            /** @var Category $productOneCategory */
            $productOneCategory = $this->getProductCategoryByProduct($testProductOne);
            /** @var Category $productTwoCategory */
            $productTwoCategory = $this->getProductCategoryByProduct($testProductTwo);

            $this->assertCategoryDefaultLocalized($productOneCategory, $title, $shortDescription, $longDescription);
            $this->assertCategoryDefaultLocalized($productTwoCategory, $title, $shortDescription, $longDescription);
        }

        if ($title === self::DEFAULT_SUBCATEGORY_TITLE) {
            $testProductFour = $this->getProductBySku(LoadProductData::PRODUCT_4);

            /** @var Category $productOneCategory */
            $productFourCategory = $this->getProductCategoryByProduct($testProductFour);

            $this->assertCategoryDefaultLocalized($productFourCategory, $title, $shortDescription, $longDescription);
        };
    }

    /**
     * @param Category $category
     * @param string $title
     * @param string $shortDescription
     * @param string $longDescription
     */
    protected function assertCategoryDefaultLocalized(Category $category, $title, $shortDescription, $longDescription)
    {
        $this->assertEquals($title, $category->getDefaultTitle());
        $this->assertEquals($shortDescription, $category->getDefaultShortDescription());
        $this->assertEquals($longDescription, $category->getDefaultLongDescription());
    }

    /**
     * @param array  $formValues
     * @param string $title
     * @param string $shortDescription
     * @param string $longDescription
     */
    protected function assertFormDefaultLocalized($formValues, $title, $shortDescription, $longDescription)
    {
        $this->assertEquals($title, $formValues['orob2b_catalog_category[titles][values][default]']);

        $this->assertEquals(
            $shortDescription,
            $formValues['orob2b_catalog_category[shortDescriptions][values][default]']
        );

        $this->assertEquals(
            $longDescription,
            $formValues['orob2b_catalog_category[longDescriptions][values][default]']
        );
    }

    /**
     * @param array  $formValues
     * @param string $title
     * @param string $shortDescription
     * @param string $longDescription
     */
    protected function assertLocalizedValues($formValues, $title, $shortDescription, $longDescription)
    {
        foreach ($this->localizations as $localization) {
            $this->assertEquals(
                $localization->getLanguageCode().$title,
                $formValues['orob2b_catalog_category[titles][values][localizations]['.$localization->getId().'][value]']
            );

            $this->assertEquals(
                $localization->getLanguageCode().$shortDescription,
                $formValues['orob2b_catalog_category[shortDescriptions][values][localizations]['.$localization->getId().'][value]']
            );

            $this->assertEquals(
                $localization->getLanguageCode().$longDescription,
                $formValues['orob2b_catalog_category[longDescriptions][values][localizations]['.$localization->getId().'][value]']
            );
        }
    }

    /**
     * @param string $uri
     *
     * @return int
     */
    protected function getCategoryIdByUri($uri)
    {
        $router = $this->getContainer()->get('router');
        $parameters = $router->match($uri);

        $this->assertArrayHasKey('id', $parameters);

        return $parameters['id'];
    }

    /**
     * @param string $sku
     *
     * @return Product
     */
    protected function getProductBySku($sku)
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BProductBundle:Product')
            ->findOneBy(['sku' => $sku]);
    }

    /**
     * @param Product $product
     *
     * @return Category|null
     */
    protected function getProductCategoryByProduct(Product $product)
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BCatalogBundle:Category')
            ->findOneByProduct($product);
    }
}
