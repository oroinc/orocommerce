<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\Form\Type;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\ProductBundle\Form\Type\BrandType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class BrandTypeTest extends WebTestCase
{
    private const DATA_CLASS = Brand::class;

    private BrandType $type;
    private FormFactoryInterface $formFactory;
    private CsrfTokenManagerInterface $tokenManager;

    protected function setUp(): void
    {
        $this->type = new BrandType();
        $this->type->setDataClass(self::DATA_CLASS);

        $this->initClient();

        $this->formFactory = $this->getContainer()->get('form.factory');
        $this->tokenManager = $this->getContainer()->get('security.csrf.token_manager');

        parent::setUp();
    }

    public function testBuildForm()
    {
        $builder = $this->createMock(FormBuilder::class);

        $builder->expects($this->exactly(5))
            ->method('add')
            ->willReturn($builder);

        $this->type->buildForm($builder, []);
    }

    public function testSubmit()
    {
        $localizationRepository = $this->getContainer()->get('doctrine')
            ->getRepository(Localization::class);

        /** @var Localization[] $localizations */
        $localizations = $localizationRepository->findAll();

        $defaultName = 'Default Title';
        $defaultDescription = 'Default Long Description';
        $defaultShortDescription = 'Default Short Description';
        $metaTitle = 'Meta title';
        $metaDescription = 'Meta description';
        $metaKeywords = 'Meta keywords';

        $submitData = [
            'names' => [ 'values' => ['default' => $defaultName]],
            'descriptions' => ['values' => [ 'default' => ['wysiwyg' => $defaultDescription]]],
            'shortDescriptions' => ['values' => ['default' => $defaultShortDescription]],
            'metaTitles' => ['values' => ['default' => $metaTitle]],
            'metaDescriptions' => ['values' => ['default' => $metaDescription]],
            'metaKeywords' => ['values' => ['default' => $metaKeywords]],
            '_token' => $this->tokenManager->getToken('brand')->getValue(),
        ];

        foreach ($localizations as $localization) {
            $localizationId = $localization->getId();
            $submitData['names']['values']['localizations'][$localizationId] = [
                'use_fallback' => true,
                'fallback' => FallbackType::SYSTEM
            ];
            $submitData['descriptions']['values']['localizations'][$localizationId] = [
                'use_fallback' => true,
                'fallback' => FallbackType::SYSTEM
            ];
            $submitData['shortDescriptions']['values']['localizations'][$localizationId] = [
                'use_fallback' => true,
                'fallback' => FallbackType::SYSTEM
            ];
        }

        $form = $this->formFactory->create(BrandType::class, new Brand());
        $form->submit($submitData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        /** @var Brand $brand */
        $brand = $form->getData();
        $this->assertInstanceOf(Brand::class, $brand);
        $this->assertEquals($defaultName, (string)$brand->getDefaultName());
        $this->assertEquals($defaultDescription, (string)$brand->getDefaultDescription());
        $this->assertEquals($defaultShortDescription, (string)$brand->getDefaultShortDescription());
        $this->assertEquals($metaTitle, (string)$brand->getMetaTitle());
        $this->assertEquals($metaDescription, (string)$brand->getMetaDescription());
        $this->assertEquals($metaKeywords, (string)$brand->getMetaKeyword());

        foreach ($localizations as $localization) {
            $this->assertLocalization($localization, $brand);
        }
    }

    private function assertLocalization(Localization $localization, Brand $brand): void
    {
        $localizedName = $this->getValueByLocalization($brand->getNames(), $localization);
        $this->assertNotEmpty($localizedName);
        $this->assertEmpty($localizedName->getString());
        $this->assertEquals(FallbackType::SYSTEM, $localizedName->getFallback());

        $localizedShortDescription = $this->getValueByLocalization($brand->getShortDescriptions(), $localization);
        $this->assertNotEmpty($localizedShortDescription);
        $this->assertEmpty($localizedShortDescription->getText());
        $this->assertEquals(FallbackType::SYSTEM, $localizedShortDescription->getFallback());

        $localizedLongDescription = $this->getValueByLocalization($brand->getDescriptions(), $localization);
        $this->assertNotEmpty($localizedLongDescription);
        $this->assertEmpty($localizedLongDescription->getText());
        $this->assertEquals(FallbackType::SYSTEM, $localizedLongDescription->getFallback());
    }

    private function getValueByLocalization(Collection $values, Localization $localization): ?LocalizedFallbackValue
    {
        $localizationId = $localization->getId();
        /** @var LocalizedFallbackValue $value */
        foreach ($values as $value) {
            if ($value->getLocalization()->getId() === $localizationId) {
                return $value;
            }
        }

        return null;
    }
}
