<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Functional\Form\Type;

use OroB2B\Bundle\CatalogBundle\Model\CategoryUnitPrecision;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Form\Type\CategoryType;
use OroB2B\Bundle\ProductBundle\Entity\Product;

/**
 * @dbIsolation
 */
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
        $this->loadFixtures(['OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData']);

        $this->formFactory = $this->getContainer()->get('form.factory');
        $this->tokenManager = $this->getContainer()->get('security.csrf.token_manager');
    }

    public function testSubmit()
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $localizationRepository = $doctrine->getRepository('OroLocaleBundle:Localization');
        $categoryRepository = $doctrine->getRepository('OroB2BCatalogBundle:Category');
        $productRepository = $doctrine->getRepository('OroB2BProductBundle:Product');
        $productUnitRepository = $doctrine->getRepository('OroB2BProductBundle:ProductUnit');

        /** @var Localization[] $localizations */
        $localizations = $localizationRepository->findAll();
        /** @var Category $parentCategory */
        $parentCategory = $categoryRepository->findOneBy([]);
        /** @var Product[] $appendedProducts */
        $appendedProducts = $productRepository->findBy([], [], 2, 0);
        /** @var Product[] $removedProducts */
        $removedProducts = $productRepository->findBy([], [], 2, 2);

        $defaultTitle = 'Default Title';
        $defaultShortDescription = 'Default Short Description';
        $defaultLongDescription = 'Default Long Description';

        /* @var $fileLocator FileLocator */
        $fileLocator = $this->getContainer()->get('file_locator');

        $smallImageName = self::SMALL_IMAGE_NAME;
        $smallImageFile = $fileLocator->locate(
            '@OroB2BCatalogBundle/Tests/Functional/DataFixtures/files/' . $smallImageName
        );
        $largeImageName = self::LARGE_IMAGE_NAME;
        $largeImageFile = $fileLocator->locate(
            '@OroB2BCatalogBundle/Tests/Functional/DataFixtures/files/' . $largeImageName
        );

        $smallImage = new UploadedFile($smallImageFile, $smallImageName, null, null, null, true);
        $largeImage = new UploadedFile($largeImageFile, $largeImageName, null, null, null, true);
        
        $productUnit = $productUnitRepository->findOneBy(['code' => 'kg']);
        $unitPrecision = new CategoryUnitPrecision();
        $unitPrecision->setUnit($productUnit)->setPrecision(3);

        // prepare input array
        $submitData = [
            'parentCategory' => $parentCategory->getId(),
            'titles' => [ 'values' => ['default' => $defaultTitle]],
            'shortDescriptions' => ['values' => ['default' => $defaultShortDescription]],
            'longDescriptions' => ['values' => [ 'default' => $defaultLongDescription]],
            'smallImage' => ['file' => $smallImage],
            'largeImage' => ['file' => $largeImage],
            'appendProducts' => implode(',', $this->getProductIds($appendedProducts)),
            'removeProducts' => implode(',', $this->getProductIds($removedProducts)),
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
        $form = $this->formFactory->create(CategoryType::NAME, new Category());
        $form->submit($submitData);
        $this->assertTrue($form->isValid());

        // assert category entity
        /** @var Category $category */
        $category = $form->getData();
        $this->assertInstanceOf('OroB2B\Bundle\CatalogBundle\Entity\Category', $category);
        $this->assertEquals($parentCategory->getId(), $category->getParentCategory()->getId());
        $this->assertEquals($defaultTitle, $category->getDefaultTitle()->getString());
        $this->assertEquals($defaultShortDescription, $category->getDefaultShortDescription()->getText());
        $this->assertEquals($defaultLongDescription, $category->getDefaultLongDescription()->getText());
        $this->assertEquals($unitPrecision, $category->getDefaultProductOptions()->getUnitPrecision());

        foreach ($localizations as $localization) {
            $this->assertLocalization($localization, $category);
        }
        // assert related products
        $this->assertEquals($appendedProducts, $form->get('appendProducts')->getData());
        $this->assertEquals($removedProducts, $form->get('removeProducts')->getData());
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
