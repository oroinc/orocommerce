<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Form\Extension;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationCollectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizationCollectionTypeStub;
use Oro\Bundle\SEOBundle\Form\Extension\BaseMetaFormExtension;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\Validation;

class BaseMetaFormExtensionTest extends FormIntegrationTestCase
{
    /**
     * @var BaseMetaFormExtension
     */
    private $extension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension = $this->getMockForAbstractClass(BaseMetaFormExtension::class);
        $this->extension->method('getMetaFieldLabelPrefix')->willReturn('prefix');
    }

    public function testBuildForm()
    {
        $builder = $this->factory->createBuilder(FormType::class);
        $this->extension->buildForm($builder, []);

        $form = $builder->getForm();

        $this->assertTrue($form->has('metaTitles'));
        $this->assertTrue($form->has('metaDescriptions'));
        $this->assertTrue($form->has('metaKeywords'));

        $this->assertEquals(
            TextareaType::class,
            $form->get('metaDescriptions')->getConfig()->getOption('entry_type')
        );
        $this->assertEquals(
            TextareaType::class,
            $form->get('metaKeywords')->getConfig()->getOption('entry_type')
        );
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        /** @var ManagerRegistry|MockObject $registry */
        $registry = $this->createMock(ManagerRegistry::class);
        $localizedFallbackValue = new LocalizedFallbackValueCollectionType($registry);

        return [
            new PreloadedExtension(
                [
                    LocalizationCollectionType::class => new LocalizationCollectionTypeStub(),
                    LocalizedFallbackValueCollectionType::class => $localizedFallbackValue
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }
}
