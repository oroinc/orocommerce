<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub\EntityChangesetTypeStub;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Form\Type\EntityChangesetType;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\TooltipFormExtensionStub;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductCollectionContentVariantType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\CollectionSortOrderGridType;
use Oro\Bundle\ProductBundle\Form\Type\ProductCollectionSegmentType;
use Oro\Bundle\ProductBundle\Form\Type\ProductCollectionVariantType;
use Oro\Bundle\ProductBundle\Service\ProductCollectionDefinitionConverter;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\CollectionSortOrderGridTypeStub;
use Oro\Bundle\SegmentBundle\Form\Type\SegmentFilterBuilderType;
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
    protected function getExtensions(): array
    {
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->any())
            ->method('getEntityManagerForClass')
            ->with(Product::class, false)
            ->willReturn($this->createMock(EntityManagerInterface::class));

        return [
            new PreloadedExtension(
                [
                    new SegmentFilterBuilderType(
                        $doctrineHelper,
                        $this->createMock(TokenStorageInterface::class)
                    ),
                    new ProductCollectionSegmentType(
                        $this->createMock(ProductCollectionDefinitionConverter::class),
                        $this->createMock(PropertyAccessor::class)
                    ),
                    CollectionSortOrderGridType::class => new CollectionSortOrderGridTypeStub(),
                    EntityChangesetType::class => new EntityChangesetTypeStub(),
                ],
                [
                    FormType::class => [new TooltipFormExtensionStub($this)]
                ]
            ),
            $this->getValidatorExtension(false)
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create(ProductCollectionVariantType::class);

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
        $form = $this->factory->create(ProductCollectionVariantType::class);
        $this->assertSame(
            ProductCollectionContentVariantType::TYPE,
            $form->getConfig()->getOptions()['content_variant_type']
        );
    }
}
