<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CatalogBundle\ContentVariantType\CategoryPageContentVariantType;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryPageVariantType;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryTreeType;
use Oro\Bundle\CatalogBundle\Tests\Unit\ContentVariantType\Stub\ContentVariantStub;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeCollectionType;
use Oro\Bundle\WebCatalogBundle\Tests\Unit\Form\Type\Stub\ScopeCollectionTypeStub;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Oro\Component\WebCatalog\Entity\WebCatalogInterface;
use Symfony\Component\Form\PreloadedExtension;

class CategoryPageVariantTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * @var CategoryPageVariantType
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

        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->type = new CategoryPageVariantType($this->registry);
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
                    CategoryTreeType::NAME => new EntityType(
                        [
                            1 => $this->getEntity(Category::class, ['id' => 1]),
                            2 => $this->getEntity(Category::class, ['id' => 2]),
                        ],
                        CategoryTreeType::NAME
                    )
                ],
                []
            ),
            $this->getValidatorExtension(true)
        ];
    }

    public function testBuildForm()
    {
        $this->assertMetadataCall();
        $form = $this->factory->create($this->type, null, ['web_catalog' => null]);

        $this->assertTrue($form->has('categoryPageCategory'));
        $this->assertTrue($form->has('scopes'));
        $this->assertTrue($form->has('type'));
    }

    public function testGetName()
    {
        $this->assertEquals(CategoryPageVariantType::NAME, $this->type->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(CategoryPageVariantType::NAME, $this->type->getBlockPrefix());
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param ContentVariantInterface $existingData
     * @param array $submittedData
     * @param int $expectedCategoryId
     * @param bool $isDefault
     */
    public function testSubmit(
        ContentVariantInterface $existingData,
        array $submittedData,
        $expectedCategoryId,
        $isDefault
    ) {
        $this->assertMetadataCall();
        $form = $this->factory->create($this->type, $existingData, ['web_catalog' => null]);

        $this->assertEquals($existingData, $form->getData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        /** @var ContentVariantStub $actualData */
        $actualData = $form->getData();

        $this->assertEquals('category_page', $actualData->getType());
        $this->assertEquals($expectedCategoryId, $actualData->getCategoryPageCategory()->getId());
        $this->assertEquals($isDefault, $actualData->isDefault());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        /** @var Category $category1 */
        $category1 = $this->getEntity(Category::class, ['id' => 1]);

        return [
            'new entity' => [
                'existingData' => new ContentVariantStub(),
                'submittedData'=> [
                    'categoryPageCategory' => 1
                ],
                'expectedCategoryId' => 1,
                'isDefault' => false
            ],
            'existing entity' => [
                'existingData' => (new ContentVariantStub())
                    ->setCategoryPageCategory($category1)
                    ->setType(CategoryPageContentVariantType::TYPE),
                'submittedData' => [
                    'categoryPageCategory' => 2,
                    'type' => 'fakeType',
                    'default' =>true
                ],
                'expectedCategoryId' => 2,
                'isDefault' => true
            ],
        ];
    }

    protected function assertMetadataCall()
    {
        /** @var ClassMetadata|\PHPUnit_Framework_MockObject_MockObject $catalogMetadata */
        $catalogMetadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $catalogMetadata->expects($this->once())
            ->method('getName')
            ->willReturn(ContentVariantStub::class);

        /** @var ClassMetadata|\PHPUnit_Framework_MockObject_MockObject $variantMetadata */
        $variantMetadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $variantMetadata->expects($this->once())
            ->method('getName')
            ->willReturn(ContentVariantStub::class);

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getClassMetadata')
            ->withConsecutive(
                [WebCatalogInterface::class],
                [ContentVariantInterface::class]
            )
            ->willReturnOnConsecutiveCalls(
                $catalogMetadata,
                $variantMetadata
            );
        $this->registry->expects($this->any())
            ->method('getManager')
            ->willReturn($em);
    }
}
