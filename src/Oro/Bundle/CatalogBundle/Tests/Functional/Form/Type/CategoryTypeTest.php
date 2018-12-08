<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Form\Type;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryType;
use Oro\Bundle\CatalogBundle\Model\CategoryUnitPrecision;
use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CategoryTypeTest extends WebTestCase
{
    const LARGE_IMAGE_NAME = 'large_image.png';
    const SMALL_IMAGE_NAME = 'small_image.png';

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var CsrfTokenManagerInterface
     */
    protected $tokenManager;

    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures(['Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData']);

        $this->formFactory = $this->getContainer()->get('form.factory');
        $this->tokenManager = $this->getContainer()->get('security.csrf.token_manager');
    }

    public function testSubmit()
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $localizationRepository = $doctrine->getRepository('OroLocaleBundle:Localization');
        $productRepository = $doctrine->getRepository('OroProductBundle:Product');
        $productUnitRepository = $doctrine->getRepository('OroProductBundle:ProductUnit');

        /** @var Localization[] $localizations */
        $localizations = $localizationRepository->findAll();
        /** @var Product[] $appendedProducts */
        $appendedProducts = $productRepository->findBy([], ['id' => 'ASC'], 2, 0);
        /** @var Product[] $removedProducts */
        $removedProducts = $productRepository->findBy([], ['id' => 'ASC'], 2, 2);

        $defaultTitle = 'Default Title';
        $defaultShortDescription = 'Default Short Description';
        $defaultLongDescription = 'Default Long Description';

        /* @var $fileLocator FileLocator */
        $fileLocator = $this->getContainer()->get('file_locator');

        $smallImageName = self::SMALL_IMAGE_NAME;
        $smallImageFile = $fileLocator->locate(
            '@OroCatalogBundle/Tests/Functional/DataFixtures/files/' . $smallImageName
        );
        $largeImageName = self::LARGE_IMAGE_NAME;
        $largeImageFile = $fileLocator->locate(
            '@OroCatalogBundle/Tests/Functional/DataFixtures/files/' . $largeImageName
        );

        $smallImage = new UploadedFile($smallImageFile, $smallImageName, null, null, null, true);
        $largeImage = new UploadedFile($largeImageFile, $largeImageName, null, null, null, true);

        $productUnit = $productUnitRepository->findOneBy(['code' => 'kg']);
        $unitPrecision = new CategoryUnitPrecision();
        $unitPrecision->setUnit($productUnit)->setPrecision(3);

        // prepare input array
        $submitData = [
            'titles' => [ 'values' => ['default' => $defaultTitle]],
            'shortDescriptions' => ['values' => ['default' => $defaultShortDescription]],
            'longDescriptions' => ['values' => [ 'default' => $defaultLongDescription]],
            'smallImage' => ['file' => $smallImage],
            'largeImage' => ['file' => $largeImage],
            'appendProducts' => implode(',', $this->getProductIds($appendedProducts)),
            'removeProducts' => implode(',', $this->getProductIds($removedProducts)),
            'defaultProductOptions' => ['unitPrecision' => ['unit' => 'kg', 'precision' => 3]],
            'inventoryThreshold' => ['scalarValue' => 0],
            LowInventoryProvider::LOW_INVENTORY_THRESHOLD_OPTION => ['scalarValue' => 0],
            '_token' => $this->tokenManager->getToken('category')->getValue(),
        ];

        foreach ($localizations as $localization) {
            $localizationId = $localization->getId();
            $submitData['titles']['values']['localizations'][$localizationId] = [
                'use_fallback' => true,
                'fallback' => FallbackType::SYSTEM
            ];
            $submitData['shortDescriptions']['values']['localizations'][$localizationId] = [
                'use_fallback' => true,
                'fallback' => FallbackType::SYSTEM
            ];
            $submitData['longDescriptions']['values']['localizations'][$localizationId] = [
                'use_fallback' => true,
                'fallback' => FallbackType::SYSTEM
            ];
        }
        // submit form
        $form = $this->formFactory->create(CategoryType::class, new Category());
        $form->submit($submitData);
        $this->assertTrue($form->isValid());

        // assert category entity
        /** @var Category $category */
        $category = $form->getData();
        $this->assertInstanceOf('Oro\Bundle\CatalogBundle\Entity\Category', $category);
        $this->assertEquals($defaultTitle, (string)$category->getDefaultTitle());
        $this->assertEquals($defaultShortDescription, (string)$category->getDefaultShortDescription());
        $this->assertEquals($defaultLongDescription, (string)$category->getDefaultLongDescription());
        $this->assertEquals($unitPrecision, $category->getDefaultProductOptions()->getUnitPrecision());

        foreach ($localizations as $localization) {
            $this->assertLocalization($localization, $category);
        }

        // assert related products
        $appendProductsData = $form->get('appendProducts')->getData();
        $this->assertCount(count($appendedProducts), $appendProductsData);
        foreach ($appendedProducts as $appendedProduct) {
            $this->assertContains($appendedProduct, $appendProductsData);
        }

        $removeProductsData = $form->get('removeProducts')->getData();
        $this->assertCount(count($removedProducts), $removeProductsData);
        foreach ($removedProducts as $removedProduct) {
            $this->assertContains($removedProduct, $removeProductsData);
        }
    }

    public function testInventoryThresholdMandatoryField()
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $localizationRepository = $doctrine->getRepository('OroLocaleBundle:Localization');
        /** @var Localization[] $localizations */
        $localizations = $localizationRepository->findAll();

        $defaultTitle = 'Default Title';
        $defaultShortDescription = 'Default Short Description';
        $defaultLongDescription = 'Default Long Description';
        // prepare input array
        $submitData = [
            'titles' => [ 'values' => ['default' => $defaultTitle]],
            'shortDescriptions' => ['values' => ['default' => $defaultShortDescription]],
            'longDescriptions' => ['values' => [ 'default' => $defaultLongDescription]],
            'defaultProductOptions' => ['unitPrecision' => ['unit' => 'kg', 'precision' => 3]],
            '_token' => $this->tokenManager->getToken('category')->getValue(),
        ];

        foreach ($localizations as $localization) {
            $localizationId = $localization->getId();
            $submitData['titles']['values']['localizations'][$localizationId] = [
                'use_fallback' => true,
                'fallback' => FallbackType::SYSTEM
            ];
            $submitData['shortDescriptions']['values']['localizations'][$localizationId] = [
                'use_fallback' => true,
                'fallback' => FallbackType::SYSTEM
            ];
            $submitData['longDescriptions']['values']['localizations'][$localizationId] = [
                'use_fallback' => true,
                'fallback' => FallbackType::SYSTEM
            ];
        }

        // submit form
        $form = $this->formFactory->create(CategoryType::class, new Category());
        $form->submit($submitData);
        $this->assertFalse($form->isValid());
        $this->assertStringStartsWith('inventoryThreshold', (string)$form->getErrors(true, false));

        $this->assertEquals(
            "ERROR: This value should not be blank.\n",
            (string)$form->get('inventoryThreshold')->getErrors(true)
        );

        $this->assertEquals(
            "ERROR: This value should not be blank.\n",
            (string)$form->get(LowInventoryProvider::LOW_INVENTORY_THRESHOLD_OPTION)->getErrors(true)
        );
    }

    /**
     * @param Product[] $products
     * @return array
     */
    protected function getProductIds(array $products)
    {
        $ids = [];
        foreach ($products as $product) {
            $ids[] = $product->getId();
        }
        return $ids;
    }

    /**
     * @param Collection|LocalizedFallbackValue[] $values
     * @param Localization $localization
     * @return LocalizedFallbackValue|null
     */
    protected function getValueByLocalization($values, Localization $localization)
    {
        $localizationId = $localization->getId();
        foreach ($values as $value) {
            if ($value->getLocalization()->getId() == $localizationId) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param Localization $localization
     * @param Category $category
     */
    protected function assertLocalization($localization, $category)
    {
        $localizedTitle = $this->getValueByLocalization($category->getTitles(), $localization);
        $this->assertNotEmpty($localizedTitle);
        $this->assertEmpty($localizedTitle->getString());
        $this->assertEquals(FallbackType::SYSTEM, $localizedTitle->getFallback());

        $localizedShortDescription = $this->getValueByLocalization(
            $category->getShortDescriptions(),
            $localization
        );
        $this->assertNotEmpty($localizedShortDescription);
        $this->assertEmpty($localizedShortDescription->getText());
        $this->assertEquals(FallbackType::SYSTEM, $localizedShortDescription->getFallback());

        $localizedLongDescription = $this->getValueByLocalization($category->getLongDescriptions(), $localization);
        $this->assertNotEmpty($localizedLongDescription);
        $this->assertEmpty($localizedLongDescription->getText());
        $this->assertEquals(FallbackType::SYSTEM, $localizedLongDescription->getFallback());
    }
}
