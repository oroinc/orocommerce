<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\EntityIdentifierType as EntityIdentifierTypeStub;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductCollectionContentVariantType;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductPageContentVariantType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Authorization\AuthorizationChecker;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\WebCatalogBundle\ContentVariantType\ContentVariantTypeRegistry;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Form\Extension\ContentNodeTypeExtension;
use Oro\Bundle\WebCatalogBundle\Form\Type\ContentNodeType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;
use Symfony\Component\Validator\Validation;

class ContentNodeTypeExtensionTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var ContentNodeTypeExtension */
    protected $extension;

    /** @var ContentVariantTypeRegistry|MockObject */
    protected $typeRegistry;

    /** @var OwnershipMetadataProviderInterface|MockObject */
    protected $metadataProvider;

    /** @var DoctrineHelper|MockObject */
    private $doctrineHelper;

    /** @var RouterInterface|MockObject */
    protected $router;

    protected function setUp(): void
    {
        $this->typeRegistry = $this->createMock(ContentVariantTypeRegistry::class);
        $this->metadataProvider = $this->createMock(OwnershipMetadataProviderInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->extension = new ContentNodeTypeExtension(
            $this->typeRegistry,
            $this->metadataProvider,
            $this->doctrineHelper
        );

        parent::setUp();
    }

    public function testBuildForm()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('addEventListener')
            ->with(FormEvents::POST_SUBMIT, [$this->extension, 'onPostSubmit'], 255);

        $this->extension->buildForm($builder, []);
    }

    public function testOnPostSubmitProductCollectionContentVariant()
    {
        $node = $this->createContentNode();
        $organization = $node->getWebCatalog()->getOrganization();
        $contentVariant = $node->getContentVariants()->first();

        $segment = $this->getEntity(Segment::class, []);
        $pcContentVariantType = $this->createProductCollectionContentVariantType($segment, $contentVariant);

        $this->typeRegistry->expects($this->once())
            ->method('getContentVariantTypeByContentVariant')
            ->with($contentVariant)
            ->willReturn($pcContentVariantType);

        $this->assertFillOrganizationForNewEntitiesCalls($segment, OwnershipMetadata::OWNER_TYPE_ORGANIZATION);

        $form = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, $node);

        $this->extension->onPostSubmit($event);

        $this->assertEquals($segment->getOrganization(), $organization);
    }

    public function testOnPostSubmitProductPageContentVariant()
    {
        $node = $this->createContentNode();
        $organization = $node->getWebCatalog()->getOrganization();
        $contentVariant = $node->getContentVariants()->first();

        $product = $this->getEntity(Product::class, ['id' => 567]);
        $ppContentVariantType = $this->createProductPageContentVariantType($product, $contentVariant);

        $this->typeRegistry->expects($this->once())
            ->method('getContentVariantTypeByContentVariant')
            ->with($contentVariant)
            ->willReturn($ppContentVariantType);

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->willReturn(567);

        $this->metadataProvider->expects($this->never())
            ->method('getMetadata');

        $form = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, $node);

        $this->extension->onPostSubmit($event);

        $this->assertNotEquals($product->getOrganization(), $organization);
    }

    public function testGetExtendedTypes()
    {
        $this->assertEquals([ContentNodeType::class], ContentNodeTypeExtension::getExtendedTypes());
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $this->router = $this->createMock(RouterInterface::class);

        return [
            new PreloadedExtension(
                [
                    ContentNodeType::class => new ContentNodeType($this->router),
                    EntityIdentifierType::class => new EntityIdentifierTypeStub(
                        [
                            1 => $this->getEntity(ContentNode::class, ['id' => 1])
                        ]
                    ),
                ],
                [
                    ContentNodeType::class => [$this->extension],
                ]
            ),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    /**
     * @return ContentNode
     */
    private function createContentNode(): ContentNode
    {
        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => 123]);
        /** @var WebCatalog $webCatalog */
        $webCatalog = $this->getEntity(WebCatalog::class, ['id' => 234]);
        /** @var Organization $organization */
        $organization = $this->getEntity(Organization::class, ['id' => 345]);
        /** @var ContentVariant $contentVariant */
        $contentVariant = $this->getEntity(ContentVariant::class, ['id' => 456]);
        /** @var Segment $segment */

        $contentVariant->setType(ProductPageContentVariantType::TYPE);

        $node->setWebCatalog($webCatalog->setOrganization($organization))
            ->addContentVariant($contentVariant);

        return $node;
    }

    /**
     * @param $product
     * @param $contentVariant
     * @return ProductPageContentVariantType
     */
    private function createProductPageContentVariantType($product, $contentVariant): ProductPageContentVariantType
    {
        /** @var ProductCollectionContentVariantType|MockObject */
        $authorizationChecker = $this->createMock(AuthorizationChecker::class);
        $propertyAccessor = $this->createMock(PropertyAccessor::class);
        $ppContentVariantType = new ProductPageContentVariantType($authorizationChecker, $propertyAccessor);

        /** @var Product $product */
        $anotherOrganization = $this->getEntity(Organization::class, ['id' => 3456]);
        $product->setOrganization($anotherOrganization);
        $propertyAccessor->expects($this->once())
            ->method('getValue')
            ->with($contentVariant, 'productPageProduct')
            ->willReturn($product);

        return $ppContentVariantType;
    }

    /**
     * @param object $segment
     * @param $contentVariant
     * @return mixed
     */
    private function createProductCollectionContentVariantType(object $segment, $contentVariant)
    {
        /** @var ProductCollectionContentVariantType|MockObject */
        $pcContentVariantType = $this->createMock(ProductCollectionContentVariantType::class);

        $anotherOrganization = $this->getEntity(Organization::class, ['id' => 3456]);
        $segment->setOrganization($anotherOrganization);
        $pcContentVariantType->expects($this->once())
            ->method('getAttachedEntity')
            ->with($contentVariant)
            ->willReturn($segment);

        return $pcContentVariantType;
    }

    private function assertFillOrganizationForNewEntitiesCalls(
        object $attachedEntity,
        int $ownership
    ) {
        $metadata = $this->createMock(OwnershipMetadataInterface::class);

        $this->metadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with(ClassUtils::getRealClass($attachedEntity))
            ->willReturn($metadata);

        $metadata->expects($this->once())
            ->method('getOwnerType')
            ->willReturn($ownership);
        $metadata->expects($this->once())
            ->method('hasOwner')
            ->willReturn(true);

        switch ($ownership) {
            case OwnershipMetadata::OWNER_TYPE_BUSINESS_UNIT:
            case OwnershipMetadata::OWNER_TYPE_USER:
                $metadata->expects($this->once())
                    ->method('getOrganizationFieldName')
                    ->willReturn('organization');
                break;
            case OwnershipMetadata::OWNER_TYPE_ORGANIZATION:
                $metadata->expects($this->once())
                    ->method('getOwnerFieldName')
                    ->willReturn('organization');
                break;
        }
    }
}
