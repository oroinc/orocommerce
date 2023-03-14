<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\WebCatalogBundle\ContentVariantType\ContentVariantTypeRegistry;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\EventListener\ContentVariantListener;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\WebCatalog\ContentVariantTypeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Acl\Util\ClassUtils;

class ContentVariantListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ContentVariantTypeRegistry|MockObject
     */
    private $typeRegistry;

    /**
     * @var OwnershipMetadataProviderInterface|MockObject
     */
    private $metadataProvider;

    /**
     * @var DoctrineHelper|MockObject
     */
    private $doctrineHelper;

    /**
     * @var ContentVariantListener
     */
    private $listener;

    protected function setUp(): void
    {
        $this->typeRegistry = $this->createMock(ContentVariantTypeRegistry::class);
        $this->metadataProvider = $this->createMock(OwnershipMetadataProviderInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $this->listener = new ContentVariantListener(
            $this->typeRegistry,
            $this->metadataProvider,
            $this->doctrineHelper,
            $propertyAccessor
        );
    }

    /**
     * @dataProvider positiveScenarioDataProvider
     */
    public function testPrePersist(
        ContentVariant $contentVariant,
        object $attachedEntity,
        int $ownership,
        ?object $expectedOwner = null,
        ?Organization $expectedOrganization = null
    ) {
        $this->assertFillOwnershipForNewEntitiesCalls($contentVariant, $attachedEntity, $ownership);

        $this->listener->prePersist($contentVariant);

        $this->assertOwnership(
            $ownership,
            $expectedOwner,
            $this->geEntityConsideringCollection($attachedEntity),
            $expectedOrganization
        );
    }

    /**
     * @dataProvider positiveScenarioDataProvider
     */
    public function testPreUpdate(
        ContentVariant $contentVariant,
        object $attachedEntity,
        int $ownership,
        ?object $expectedOwner = null,
        ?Organization $expectedOrganization = null
    ) {
        $this->assertFillOwnershipForNewEntitiesCalls($contentVariant, $attachedEntity, $ownership);

        $this->listener->preUpdate($contentVariant);

        $this->assertOwnership(
            $ownership,
            $expectedOwner,
            $this->geEntityConsideringCollection($attachedEntity),
            $expectedOrganization
        );
    }

    public function testPrePersistNoAttachedEntity()
    {
        /** @var Organization $organization */
        $contentVariant = $this->createContentVariant();
        $type = $this->createMock(ContentVariantTypeInterface::class);

        $this->typeRegistry->expects($this->once())
            ->method('getContentVariantTypeByContentVariant')
            ->with($contentVariant)
            ->willReturn($type);

        $this->doctrineHelper->expects($this->never())
            ->method($this->anything());

        $this->metadataProvider->expects($this->never())
            ->method($this->anything());

        $this->listener->prePersist($contentVariant);
    }

    public function testPrePersistEmptyVariant()
    {
        $contentVariant = new ContentVariant();

        $this->typeRegistry->expects($this->never())
            ->method($this->anything());

        $this->doctrineHelper->expects($this->never())
            ->method($this->anything());

        $this->metadataProvider->expects($this->never())
            ->method($this->anything());

        $this->listener->prePersist($contentVariant);
    }

    public function testPrePersistExistingAttachedEntity()
    {
        $contentVariant = $this->createContentVariant();

        $attachedEntity = $this->getEntity(Segment::class, ['id' => 1]);
        $type = $this->createMock(ContentVariantWithEntity::class);
        $type->expects($this->once())
            ->method('getAttachedEntity')
            ->willReturn($attachedEntity);

        $this->typeRegistry->expects($this->once())
            ->method('getContentVariantTypeByContentVariant')
            ->with($contentVariant)
            ->willReturn($type);

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->willReturn(1);

        $this->metadataProvider->expects($this->never())
            ->method($this->anything());

        $this->listener->prePersist($contentVariant);
    }

    public function testPrePersistNoOwnership()
    {
        /** @var Organization $organization */
        $contentVariant = $this->createContentVariant();

        $attachedEntity = $this->getEntity(Segment::class, ['id' => 1]);
        $type = $this->createMock(ContentVariantWithEntity::class);
        $type->expects($this->once())
            ->method('getAttachedEntity')
            ->willReturn($attachedEntity);

        $this->typeRegistry->expects($this->once())
            ->method('getContentVariantTypeByContentVariant')
            ->with($contentVariant)
            ->willReturn($type);

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->willReturn(null);

        $metadata = $this->createMock(OwnershipMetadataInterface::class);
        $metadata->expects($this->never())
            ->method('getOwnerType');
        $metadata->expects($this->once())
            ->method('hasOwner')
            ->willReturn(false);

        $this->metadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with(ClassUtils::getRealClass($this->geEntityConsideringCollection($attachedEntity)))
            ->willReturn($metadata);

        $this->listener->prePersist($contentVariant);
    }

    public function positiveScenarioDataProvider(): array
    {
        /** @var Organization $organization */
        $contentVariant = $this->createContentVariant();
        $bu = $contentVariant->getNode()->getWebCatalog()->getOwner();
        $organization = $contentVariant->getNode()->getWebCatalog()->getOrganization();

        $buOwned = new Segment();
        $userOwned = new Consent();
        $organizationOwned = new Page();

        return [
            'business unit owner' => [
                'contentVariant' => $contentVariant,
                'attachedEntity' => $buOwned,
                'ownership' => OwnershipMetadata::OWNER_TYPE_BUSINESS_UNIT,
                'expectedOwner' => $bu,
                'expectedOrganization' => $organization
            ],
            'business unit owner COLLECTION' => [
                'contentVariant' => $contentVariant,
                'attachedEntity' => new ArrayCollection([$buOwned]),
                'ownership' => OwnershipMetadata::OWNER_TYPE_BUSINESS_UNIT,
                'expectedOwner' => $bu,
                'expectedOrganization' => $organization
            ],
            'user owner' => [
                'contentVariant' => $contentVariant,
                'attachedEntity' => $userOwned,
                'ownership' => OwnershipMetadata::OWNER_TYPE_USER,
                'expectedOwner' => null,
                'expectedOrganization' => $organization
            ],
            'organization owner' => [
                'contentVariant' => $contentVariant,
                'attachedEntity' => $organizationOwned,
                'ownership' => OwnershipMetadata::OWNER_TYPE_ORGANIZATION,
                'expectedOwner' => $organization,
                'expectedOrganization' => null
            ],
        ];
    }

    private function assertFillOwnershipForNewEntitiesCalls(
        ContentVariant $contentVariant,
        object $attachedEntity,
        int $ownership
    ) {
        $type = $this->createMock(ContentVariantWithEntity::class);
        $type->expects($this->once())
            ->method('getAttachedEntity')
            ->willReturn($attachedEntity);

        $this->typeRegistry->expects($this->once())
            ->method('getContentVariantTypeByContentVariant')
            ->with($contentVariant)
            ->willReturn($type);

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($this->geEntityConsideringCollection($attachedEntity))
            ->willReturn(null);

        $metadata = $this->createMock(OwnershipMetadataInterface::class);
        $metadata->expects($this->once())
            ->method('getOwnerType')
            ->willReturn($ownership);
        $metadata->expects($this->once())
            ->method('hasOwner')
            ->willReturn(true);

        $this->metadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with(ClassUtils::getRealClass($this->geEntityConsideringCollection($attachedEntity)))
            ->willReturn($metadata);

        switch ($ownership) {
            case OwnershipMetadata::OWNER_TYPE_BUSINESS_UNIT:
                $metadata->expects($this->once())
                    ->method('getOwnerFieldName')
                    ->willReturn('owner');
                $metadata->expects($this->once())
                    ->method('getOrganizationFieldName')
                    ->willReturn('organization');
                break;
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

    private function assertOwnership(
        int $ownership,
        ?object $expectedOwner,
        object $attachedEntity,
        ?Organization $expectedOrganization
    ): void {
        switch ($ownership) {
            case OwnershipMetadata::OWNER_TYPE_BUSINESS_UNIT:
                $this->assertEquals($expectedOwner, $attachedEntity->getOwner());
                $this->assertEquals($expectedOrganization, $attachedEntity->getOrganization());
                break;
            case OwnershipMetadata::OWNER_TYPE_USER:
                $this->assertEquals($expectedOrganization, $attachedEntity->getOrganization());
                break;
            case OwnershipMetadata::OWNER_TYPE_ORGANIZATION:
                $this->assertEquals($expectedOwner, $attachedEntity->getOrganization());
                break;
        }
    }

    /**
     * @param object $attachedEntity
     * @return object
     */
    private function geEntityConsideringCollection(object $attachedEntity)
    {
        if ($attachedEntity instanceof Collection) {
            return $attachedEntity->first();
        }

        return $attachedEntity;
    }

    private function createContentVariant(): ContentVariant
    {
        /** @var Organization $organization */
        $organization = $this->getEntity(Organization::class, ['id' => 2]);
        /** @var BusinessUnit $bu */
        $bu = $this->getEntity(BusinessUnit::class, ['id' => 3]);

        $webCatalog = new WebCatalog();
        $webCatalog->setOrganization($organization);
        $webCatalog->setOwner($bu);

        $contentNode = new ContentNode();
        $contentNode->setWebCatalog($webCatalog);

        $contentVariant = new ContentVariant();
        $contentVariant->setNode($contentNode);

        return $contentVariant;
    }
}
