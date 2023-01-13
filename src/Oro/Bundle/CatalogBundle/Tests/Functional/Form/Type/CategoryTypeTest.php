<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Form\Type;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryType;
use Oro\Bundle\CatalogBundle\Model\CategoryUnitPrecision;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CategoryTypeTest extends WebTestCase
{
    private const LARGE_IMAGE_NAME = 'large_image.png';
    private const SMALL_IMAGE_NAME = 'small_image.png';

    private FormFactoryInterface $formFactory;
    private CsrfTokenManagerInterface $tokenManager;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadCategoryProductData::class]);

        $this->formFactory = $this->getContainer()->get('form.factory');
        $this->tokenManager = $this->getContainer()->get('security.csrf.token_manager');
    }

    public function testSubmit()
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $localizationRepository = $doctrine->getRepository(Localization::class);
        $productRepository = $doctrine->getRepository(Product::class);
        $productUnitRepository = $doctrine->getRepository(ProductUnit::class);

        /** @var Localization[] $localizations */
        $localizations = $localizationRepository->findAll();
        /** @var Product[] $appendedProducts */
        $appendedProducts = $productRepository->findBy([], ['id' => 'ASC'], 2, 0);
        /** @var Product[] $removedProducts */
        $removedProducts = $productRepository->findBy([], ['id' => 'ASC'], 2, 2);
        $sortOrders = [2 => ['categorySortOrder' => 0.2]];

        $defaultTitle = 'Default Title';
        $defaultShortDescription = 'Default Short Description';
        $defaultLongDescription = 'Default Long Description';

        /* @var FileLocator $fileLocator */
        $fileLocator = $this->getContainer()->get('file_locator');

        $smallImageName = self::SMALL_IMAGE_NAME;
        $smallImageFile = $fileLocator->locate(
            '@OroCatalogBundle/Tests/Functional/DataFixtures/files/' . $smallImageName
        );
        $largeImageName = self::LARGE_IMAGE_NAME;
        $largeImageFile = $fileLocator->locate(
            '@OroCatalogBundle/Tests/Functional/DataFixtures/files/' . $largeImageName
        );

        $smallImage = new UploadedFile($smallImageFile, $smallImageName, null, null, true);
        $largeImage = new UploadedFile($largeImageFile, $largeImageName, null, null, true);

        $productUnit = $productUnitRepository->findOneBy(['code' => 'kg']);
        $unitPrecision = new CategoryUnitPrecision();
        $unitPrecision->setUnit($productUnit)->setPrecision(3);

        // prepare input array
        $submitData = [
            'titles' => [ 'values' => ['default' => $defaultTitle]],
            'shortDescriptions' => ['values' => ['default' => $defaultShortDescription]],
            'longDescriptions' => ['values' => [ 'default' => ['wysiwyg' => $defaultLongDescription]]],
            'smallImage' => ['file' => $smallImage],
            'largeImage' => ['file' => $largeImage],
            'appendProducts' => implode(',', $this->getProductIds($appendedProducts)),
            'removeProducts' => implode(',', $this->getProductIds($removedProducts)),
            'sortOrder' => json_encode($sortOrders),
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
        $this->assertTrue($form->isSynchronized());

        // assert category entity
        /** @var Category $category */
        $category = $form->getData();
        $this->assertInstanceOf(Category::class, $category);
        $this->assertEquals($defaultTitle, (string)$category->getDefaultTitle());
        $this->assertEquals($defaultShortDescription, (string)$category->getDefaultShortDescription());
        $this->assertEquals($defaultLongDescription, (string)$category->getDefaultLongDescription());
        $this->assertEquals($unitPrecision, $category->getDefaultProductOptions()->getUnitPrecision());

        foreach ($localizations as $localization) {
            $this->assertLocalization($localization, $category);
        }

        $this->assertRelatedProducts($form, $appendedProducts, $removedProducts);

        $this->assertSortOrders($form, $sortOrders);
    }

    public function testInventoryThresholdMandatoryField()
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $localizationRepository = $doctrine->getRepository(Localization::class);
        /** @var Localization[] $localizations */
        $localizations = $localizationRepository->findAll();

        $defaultTitle = 'Default Title';
        $defaultShortDescription = 'Default Short Description';
        $defaultLongDescription = 'Default Long Description';
        // prepare input array
        $submitData = [
            'titles' => [ 'values' => ['default' => $defaultTitle]],
            'shortDescriptions' => ['values' => ['default' => $defaultShortDescription]],
            'longDescriptions' => ['values' => [ 'default' => ['wysiwyg' => $defaultLongDescription]]],
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
        $this->assertTrue($form->isSynchronized());
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

    private function getProductIds(array $products): array
    {
        $ids = [];
        /** @var Product $product */
        foreach ($products as $product) {
            $ids[] = $product->getId();
        }
        return $ids;
    }

    private function getValueByLocalization(
        Collection $values,
        Localization $localization
    ): ?AbstractLocalizedFallbackValue {
        $localizationId = $localization->getId();
        /** @var LocalizedFallbackValue $value */
        foreach ($values as $value) {
            if ($value->getLocalization()->getId() === $localizationId) {
                return $value;
            }
        }

        return null;
    }

    private function assertLocalization(Localization $localization, Category $category): void
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

    private function assertRelatedProducts(
        FormInterface $form,
        array $appendedProducts,
        array $removedProducts
    ): void {
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

    private function assertSortOrders(FormInterface $form, array $sortOrders): void
    {
        $sortOrdersData = array_map(
            function ($row) {
                return $row['data'];
            },
            $form->get('sortOrder')->getData()->toArray()
        );
        $this->assertCount(count($sortOrders), $sortOrdersData);
        foreach ($sortOrders as $sortOrder) {
            $this->assertContains($sortOrder, $sortOrdersData);
        }
    }
}
