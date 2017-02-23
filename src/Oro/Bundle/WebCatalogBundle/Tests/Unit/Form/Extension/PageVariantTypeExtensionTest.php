<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Component\WebCatalog\ContentVariantTypeInterface;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Oro\Component\WebCatalog\Entity\WebCatalogInterface;
use Oro\Component\WebCatalog\Form\PageVariantType;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeCollectionType;
use Oro\Bundle\ScopeBundle\Tests\Unit\Form\Type\Stub\ScopeCollectionTypeStub;
use Oro\Bundle\WebCatalogBundle\Form\Extension\PageVariantTypeExtension;

class PageVariantTypeExtensionTest extends FormIntegrationTestCase
{
    /**
     * @var PageVariantTypeExtension
     */
    protected $extension;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    protected function setUp()
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->extension = new PageVariantTypeExtension($this->registry);
        parent::setUp();
    }

    public function testBuildForm()
    {
        $this->prepareRegistry();
        $pageContentVariantType = ContentVariantTypeInterface::class;

        $form = $this->factory->create(PageVariantType::class, null, [
            'content_variant_type' => $pageContentVariantType,
            'web_catalog' => null,
        ]);

        $this->assertTrue($form->has('scopes'));
        $this->assertTrue($form->has('type'));
        $this->assertTrue($form->has('default'));

        $submittedData = $this->createMock(ContentVariantInterface::class);
        $submittedData->expects($this->once())->method('setType')->with($pageContentVariantType);
        $form->submit($submittedData);
    }

    public function testRequiredOptionContentVariantType()
    {
        $this->prepareRegistry();
        $this->expectException(MissingOptionsException::class);
        $this->factory->create(PageVariantType::class, null, [
            'web_catalog' => null,
        ]);
    }

    public function testRequiredOptionWebCatalog()
    {
        $this->prepareRegistry();
        $this->expectException(MissingOptionsException::class);
        $this->factory->create(PageVariantType::class, null, [
            'content_variant_type' => ContentVariantTypeInterface::class,
        ]);
    }

    public function testRequiredOptionWebCatalogInvalidOption()
    {
        $this->prepareRegistry();
        $this->expectException(InvalidOptionsException::class);
        $this->factory->create(PageVariantType::class, null, [
            'content_variant_type' => ContentVariantTypeInterface::class,
            'web_catalog' => new \stdClass(),
        ]);
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(PageVariantType::class, $this->extension->getExtendedType());
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    ScopeCollectionType::NAME => new ScopeCollectionTypeStub(),
                ],
                [
                    PageVariantType::NAME => [$this->extension],
                ]
            )
        ];
    }

    protected function prepareRegistry()
    {
        $catalogMetadata = $this->createMock(ClassMetadata::class);
        $catalogMetadata->expects($this->once())->method('getName')->willReturn(WebCatalogInterface::class);

        $variantMetadata = $this->createMock(ClassMetadata::class);
        $variantMetadata->expects($this->once())->method('getName')->willReturn(ContentVariantInterface::class);

        $em = $this->createMock(ObjectManager::class);
        $em->expects($this->exactly(2))
            ->method('getClassMetadata')
            ->withConsecutive(
                [WebCatalogInterface::class],
                [ContentVariantInterface::class]
            )
            ->willReturnOnConsecutiveCalls(
                $catalogMetadata,
                $variantMetadata
            );

        $this->registry->expects($this->once())
            ->method('getManager')
            ->willReturn($em);
    }
}
