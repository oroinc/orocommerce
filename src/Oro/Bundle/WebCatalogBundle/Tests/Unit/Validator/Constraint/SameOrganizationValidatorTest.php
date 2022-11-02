<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Validator\Constraint;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Oro\Bundle\WebCatalogBundle\ContentVariantType\ContentVariantTypeRegistry;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Tests\Unit\EventListener\ContentVariantWithEntity;
use Oro\Bundle\WebCatalogBundle\Validator\Constraint\NotEmptyScopes;
use Oro\Bundle\WebCatalogBundle\Validator\Constraint\SameOrganizationValidator;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\WebCatalog\ContentVariantTypeInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class SameOrganizationValidatorTest extends ConstraintValidatorTestCase
{
    /** @var ContentVariantTypeRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $typeRegistry;

    /** @var EntityOwnerAccessor|\PHPUnit\Framework\MockObject\MockObject */
    private $entityOwnerAccessor;

    protected function setUp(): void
    {
        $this->typeRegistry = $this->createMock(ContentVariantTypeRegistry::class);
        $this->entityOwnerAccessor = $this->createMock(EntityOwnerAccessor::class);
        parent::setUp();
    }

    protected function createValidator(): SameOrganizationValidator
    {
        return new SameOrganizationValidator($this->typeRegistry, $this->entityOwnerAccessor);
    }

    private function getOrganization(int $id): Organization
    {
        $organization = new Organization();
        $organization->setId($id);

        return $organization;
    }

    private function getProduct(int $id): Product
    {
        $product = new Product();
        ReflectionUtil::setId($product, $id);

        return $product;
    }

    public function testValidateNull()
    {
        $constraint = new NotEmptyScopes();
        $this->validator->validate(null, $constraint);
        $this->assertNoViolation();
    }

    public function testValidateNotContentVariant()
    {
        $constraint = new NotEmptyScopes();
        $this->validator->validate(new \stdClass(), $constraint);
        $this->assertNoViolation();
    }

    public function testValidateWithoutAttachedEntity()
    {
        $organization = $this->getOrganization(1);

        $attachedEntity = $this->getProduct(2);
        $attachedEntity->setOrganization($organization);

        $webCatalog = new WebCatalog();
        $webCatalog->setOrganization($organization);

        $contentNode = new ContentNode();
        $contentNode->setWebCatalog($webCatalog);

        $contentVariant = new ContentVariant();
        $contentVariant->setNode($contentNode);

        $type = $this->createMock(ContentVariantTypeInterface::class);

        $this->typeRegistry->expects($this->once())
            ->method('getContentVariantTypeByContentVariant')
            ->with($contentVariant)
            ->willReturn($type);

        $this->entityOwnerAccessor->expects($this->never())
            ->method('getOrganization');

        $constraint = new NotEmptyScopes();
        $this->validator->validate($contentVariant, $constraint);
        $this->assertNoViolation();
    }

    public function testValidateNoOrganization()
    {
        $organization = $this->getOrganization(1);

        $attachedEntity = $this->getProduct(2);
        $attachedEntity->setOrganization($organization);

        $webCatalog = new WebCatalog();
        $webCatalog->setOrganization($organization);

        $contentNode = new ContentNode();
        $contentNode->setWebCatalog($webCatalog);

        $contentVariant = new ContentVariant();
        $contentVariant->setNode($contentNode);

        $type = $this->createMock(ContentVariantWithEntity::class);
        $type->expects($this->once())
            ->method('getAttachedEntity')
            ->willReturn($attachedEntity);

        $this->typeRegistry->expects($this->once())
            ->method('getContentVariantTypeByContentVariant')
            ->with($contentVariant)
            ->willReturn($type);

        $this->entityOwnerAccessor->expects($this->once())
            ->method('getOrganization')
            ->with($attachedEntity)
            ->willReturn(null);

        $constraint = new NotEmptyScopes();
        $this->validator->validate($contentVariant, $constraint);
        $this->assertNoViolation();
    }

    public function testValidateValid()
    {
        $organization = $this->getOrganization(1);

        $attachedEntity = $this->getProduct(2);
        $attachedEntity->setOrganization($organization);

        $webCatalog = new WebCatalog();
        $webCatalog->setOrganization($organization);

        $contentNode = new ContentNode();
        $contentNode->setWebCatalog($webCatalog);

        $contentVariant = new ContentVariant();
        $contentVariant->setNode($contentNode);

        $type = $this->createMock(ContentVariantWithEntity::class);
        $type->expects($this->once())
            ->method('getAttachedEntity')
            ->willReturn($attachedEntity);

        $this->typeRegistry->expects($this->once())
            ->method('getContentVariantTypeByContentVariant')
            ->with($contentVariant)
            ->willReturn($type);

        $this->entityOwnerAccessor->expects($this->once())
            ->method('getOrganization')
            ->with($attachedEntity)
            ->willReturn($organization);

        $constraint = new NotEmptyScopes();
        $this->validator->validate($contentVariant, $constraint);
        $this->assertNoViolation();
    }

    public function testValidateValidCollection()
    {
        $organization = $this->getOrganization(1);

        $attachedEntity = $this->getProduct(2);
        $attachedEntity->setOrganization($organization);

        $webCatalog = new WebCatalog();
        $webCatalog->setOrganization($organization);

        $contentNode = new ContentNode();
        $contentNode->setWebCatalog($webCatalog);

        $contentVariant = new ContentVariant();
        $contentVariant->setNode($contentNode);

        $type = $this->createMock(ContentVariantWithEntity::class);
        $type->expects($this->once())
            ->method('getAttachedEntity')
            ->willReturn(new ArrayCollection([$attachedEntity]));

        $this->typeRegistry->expects($this->once())
            ->method('getContentVariantTypeByContentVariant')
            ->with($contentVariant)
            ->willReturn($type);

        $this->entityOwnerAccessor->expects($this->once())
            ->method('getOrganization')
            ->with($attachedEntity)
            ->willReturn($organization);

        $constraint = new NotEmptyScopes();
        $this->validator->validate($contentVariant, $constraint);
        $this->assertNoViolation();
    }

    public function testValidateInvalid()
    {
        $organization = $this->getOrganization(1);
        $organization2 = $this->getOrganization(2);

        $attachedEntity = $this->getProduct(2);
        $attachedEntity->setOrganization($organization2);

        $webCatalog = new WebCatalog();
        $webCatalog->setOrganization($organization);

        $contentNode = new ContentNode();
        $contentNode->setWebCatalog($webCatalog);

        $contentVariant = new ContentVariant();
        $contentVariant->setNode($contentNode);

        $type = $this->createMock(ContentVariantWithEntity::class);
        $type->expects($this->once())
            ->method('getAttachedEntity')
            ->willReturn($attachedEntity);

        $this->typeRegistry->expects($this->once())
            ->method('getContentVariantTypeByContentVariant')
            ->with($contentVariant)
            ->willReturn($type);

        $this->entityOwnerAccessor->expects($this->once())
            ->method('getOrganization')
            ->with($attachedEntity)
            ->willReturn($organization2);

        $constraint = new NotEmptyScopes();
        $this->validator->validate($contentVariant, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }
}
