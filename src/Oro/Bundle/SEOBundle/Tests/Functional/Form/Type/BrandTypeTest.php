<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\Form\Type;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\ProductBundle\Form\Type\BrandType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class BrandTypeTest extends WebTestCase
{
    use EntityTrait;

    const DATA_CLASS = Brand::class;

    /**
     * @var BrandType
     */
    protected $type;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var CsrfTokenManagerInterface
     */
    protected $tokenManager;

    protected function setUp(): void
    {
        /** @var LocaleSettings|\PHPUnit\Framework\MockObject\MockObject */
        $localeSettings = $this->createMock(LocaleSettings::class);
        /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
        $configManager = $this->createMock(ConfigManager::class);

        $this->type = new BrandType($configManager, $localeSettings);
        $this->type->setDataClass(self::DATA_CLASS);

        $this->initClient();

        $this->formFactory = $this->getContainer()->get('form.factory');
        $this->tokenManager = $this->getContainer()->get('security.csrf.token_manager');

        parent::setUp();
    }

    protected function tearDown(): void
    {
        unset($this->type);
    }

    public function testBuildForm()
    {
        /** @var FormBuilder|\PHPUnit\Framework\MockObject\MockObject $builder */
        $builder = $this->createMock(FormBuilder::class);

        $builder->expects($this->exactly(5))
            ->method('add')
            ->willReturn($builder);

        $this->type->buildForm($builder, []);
    }

    public function testSubmit()
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $localizationRepository = $doctrine->getRepository('OroLocaleBundle:Localization');

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

    /**
     * @param Localization $localization
     * @param Brand $brand
     */
    protected function assertLocalization($localization, $brand)
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
}
