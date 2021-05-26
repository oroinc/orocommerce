<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\ScopeBundle\Form\Type\ScopeCollectionType;
use Oro\Bundle\ScopeBundle\Tests\Unit\Form\Type\Stub\ScopeCollectionTypeStub;
use Oro\Bundle\WebCatalogBundle\Form\Extension\PageVariantTypeExtension;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Oro\Component\WebCatalog\ContentVariantTypeInterface;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Oro\Component\WebCatalog\Form\PageVariantType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class PageVariantTypeExtensionTest extends FormIntegrationTestCase
{
    /**
     * @var PageVariantTypeExtension
     */
    protected $extension;

    protected function setUp(): void
    {
        $this->extension = new PageVariantTypeExtension();
        parent::setUp();
    }

    public function testBuildForm()
    {
        $pageContentVariantType = ContentVariantTypeInterface::class;

        $form = $this->factory->create(PageVariantType::class, null, [
            'content_variant_type' => $pageContentVariantType,
            'web_catalog' => null,
        ]);

        $this->assertTrue($form->has('scopes'));
        $this->assertTrue($form->has('type'));
        $this->assertTrue($form->has('default'));
        $this->assertTrue($form->has('expanded'));

        $submittedData = $this->createMock(ContentVariantInterface::class);
        $submittedData->expects($this->once())->method('setType')->with($pageContentVariantType);
        $form->submit($submittedData);
    }

    public function testRequiredOptionContentVariantType()
    {
        $this->expectException(MissingOptionsException::class);
        $this->factory->create(PageVariantType::class, null, [
            'web_catalog' => null,
        ]);
    }

    public function testRequiredOptionWebCatalog()
    {
        $this->expectException(MissingOptionsException::class);
        $this->factory->create(PageVariantType::class, null, [
            'content_variant_type' => ContentVariantTypeInterface::class,
        ]);
    }

    public function testRequiredOptionWebCatalogInvalidOption()
    {
        $this->expectException(InvalidOptionsException::class);
        $this->factory->create(PageVariantType::class, null, [
            'content_variant_type' => ContentVariantTypeInterface::class,
            'web_catalog' => new \stdClass(),
        ]);
    }

    public function testGetExtendedTypes(): void
    {
        $this->assertEquals([PageVariantType::class], PageVariantTypeExtension::getExtendedTypes());
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    ScopeCollectionType::class => new ScopeCollectionTypeStub(),
                ],
                [
                    PageVariantType::class => [$this->extension],
                ]
            )
        ];
    }
}
