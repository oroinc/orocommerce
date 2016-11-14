<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductPageContentVariantType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductPageVariantType;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ContentVariantStub;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeCollectionType;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Form\Type\SystemPageVariantType;
use Oro\Bundle\WebCatalogBundle\Tests\Unit\Form\Type\Stub\ScopeCollectionTypeStub;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Symfony\Component\Form\PreloadedExtension;

class ProductPageVariantTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * @var SystemPageVariantType
     */
    protected $type;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->registry = $this->getMock(ManagerRegistry::class);
        $this->type = new ProductPageVariantType($this->registry);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        unset($this->type);
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    ScopeCollectionType::NAME => new ScopeCollectionTypeStub(),
                    ProductSelectType::NAME => new EntityType(
                        [
                            1 => $this->getEntity(Product::class, ['id' => 1]),
                            2 => $this->getEntity(Product::class, ['id' => 2]),
                        ],
                        ProductSelectType::NAME
                    )
                ],
                []
            )
        ];
    }

    public function testBuildForm()
    {
        $this->assertMetadataCall();
        $form = $this->factory->create($this->type);

        $this->assertTrue($form->has('productPageProduct'));
        $this->assertTrue($form->has('scopes'));
        $this->assertTrue($form->has('type'));
    }

    public function testGetName()
    {
        $this->assertEquals(ProductPageVariantType::NAME, $this->type->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(ProductPageVariantType::NAME, $this->type->getBlockPrefix());
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param WebCatalog $existingData
     * @param array $submittedData
     * @param WebCatalog $expectedData
     */
    public function testSubmit($existingData, $submittedData, $expectedData)
    {
        $this->assertMetadataCall();
        $form = $this->factory->create($this->type, $existingData);

        $this->assertEquals($existingData, $form->getData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        $product1 = $this->getEntity(Product::class, ['id' => 1]);
        $product2 = $this->getEntity(Product::class, ['id' => 2]);

        return [
            'new entity' => [
                new ContentVariantStub(),
                [
                    'productPageProduct' => 1
                ],
                (new ContentVariantStub())
                    ->setProductPageProduct($product1)
                    ->setType(ProductPageContentVariantType::TYPE)
            ],
            'existing entity' => [
                (new ContentVariantStub())
                    ->setProductPageProduct($product1)
                    ->setType(ProductPageContentVariantType::TYPE),
                [
                    'productPageProduct' => 2,
                    'type' => 'fakeType'
                ],
                (new ContentVariantStub())
                    ->setProductPageProduct($product2)
                    ->setType(ProductPageContentVariantType::TYPE)
            ],
        ];
    }

    protected function assertMetadataCall()
    {
        /** @var ClassMetadata|\PHPUnit_Framework_MockObject_MockObject $metadata */
        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->once())
            ->method('getName')
            ->willReturn(ContentVariantStub::class);
        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getClassMetadata')
            ->with(ContentVariantInterface::class)
            ->willReturn($metadata);
        $this->registry->expects($this->any())
            ->method('getManager')
            ->willReturn($em);
    }
}
