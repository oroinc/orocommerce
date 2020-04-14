<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductCollectionContentVariantType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductCollectionSegmentType;
use Oro\Bundle\ProductBundle\Form\Type\ProductCollectionVariantType;
use Oro\Bundle\ProductBundle\Service\ProductCollectionDefinitionConverter;
use Oro\Bundle\SegmentBundle\Form\Type\SegmentFilterBuilderType;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ProductCollectionVariantTypeTest extends FormIntegrationTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $em = $this->createMock(EntityManagerInterface::class);

        /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject $doctrineHelper */
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->any())
            ->method('getEntityManagerForClass')
            ->with(Product::class, false)
            ->willReturn($em);

        /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject $tokenStorage */
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $segmentFilterBuilderType = new SegmentFilterBuilderType($doctrineHelper, $tokenStorage);

        /** @var ProductCollectionDefinitionConverter|\PHPUnit\Framework\MockObject\MockObject $definitionConverter */
        $definitionConverter = $this->createMock(ProductCollectionDefinitionConverter::class);
        /** @var PropertyAccessor|\PHPUnit\Framework\MockObject\MockObject $propertyAccessor */
        $propertyAccessor = $this->createMock(PropertyAccessor::class);
        $productCollectionSegmentType = new ProductCollectionSegmentType($definitionConverter, $propertyAccessor);

        $configProvider = $this
            ->getMockBuilder(ConfigProvider::class)
            ->setMethods(['hasConfig', 'getConfig', 'get'])
            ->disableOriginalConstructor()
            ->getMock();

        $translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $translator
            ->method('trans')
            ->willReturnCallback(function ($string) {
                return $string . '.trans';
            });

        return [
            new PreloadedExtension(
                [
                    SegmentFilterBuilderType::class => $segmentFilterBuilderType,
                    ProductCollectionSegmentType::class => $productCollectionSegmentType
                ],
                [
                    FormType::class => [new TooltipFormExtension($configProvider, $translator)],
                ]
            ),
            $this->getValidatorExtension(false)
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create(ProductCollectionVariantType::class, null);

        $this->assertTrue($form->has('productCollectionSegment'));
        $this->assertTrue($form->has('overrideVariantConfiguration'));
        $this->assertEquals(
            ProductCollectionContentVariantType::TYPE,
            $form->getConfig()->getOption('content_variant_type')
        );
    }

    public function testGetBlockPrefix()
    {
        $type = new ProductCollectionVariantType();
        $this->assertEquals(ProductCollectionVariantType::NAME, $type->getBlockPrefix());
    }

    public function testDefaultOptions()
    {
        $form = $this->factory->create(ProductCollectionVariantType::class, null);
        $this->assertSame(
            ProductCollectionContentVariantType::TYPE,
            $form->getConfig()->getOptions()['content_variant_type']
        );
    }
}
