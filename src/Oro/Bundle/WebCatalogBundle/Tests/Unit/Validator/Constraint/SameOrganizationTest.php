<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Validator\Constraint;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Oro\Bundle\WebCatalogBundle\ContentVariantType\ContentVariantTypeRegistry;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Tests\Unit\EventListener\ContentVariantWithEntity;
use Oro\Bundle\WebCatalogBundle\Validator\Constraint\NotEmptyScopes;
use Oro\Bundle\WebCatalogBundle\Validator\Constraint\SameOrganization;
use Oro\Bundle\WebCatalogBundle\Validator\Constraint\SameOrganizationValidator;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\WebCatalog\ContentVariantTypeInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class SameOrganizationTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ContentVariantTypeRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $typeRegistry;

    /**
     * @var EntityOwnerAccessor|\PHPUnit\Framework\MockObject\MockObject
     */
    private $entityOwnerAccessor;

    /**
     * @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $context;

    /**
     * @var SameOrganizationValidator
     */
    protected $validator;

    /**
     * @var SameOrganization
     */
    protected $constraint;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->typeRegistry = $this->createMock(ContentVariantTypeRegistry::class);
        $this->entityOwnerAccessor = $this->createMock(EntityOwnerAccessor::class);
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->constraint = new NotEmptyScopes();

        $this->validator = new SameOrganizationValidator($this->typeRegistry, $this->entityOwnerAccessor);
        $this->validator->initialize($this->context);
    }

    public function testValidateNull()
    {
        $value = null;
        $this->context->expects($this->never())
            ->method($this->anything());

        $this->validator->validate($value, $this->constraint);
    }

    public function testValidateNotContentVariant()
    {
        $value = new \stdClass();
        $this->context->expects($this->never())
            ->method($this->anything());

        $this->validator->validate($value, $this->constraint);
    }

    public function testValidateWithoutAttachedEntity()
    {
        /** @var OrganizationInterface $organization */
        $organization = $this->getEntity(Organization::class, ['id' => 1]);

        /** @var Product $attachedEntity */
        $attachedEntity = $this->getEntity(Product::class, ['id' => 2]);
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

        $this->context->expects($this->never())
            ->method($this->anything());

        $this->validator->validate($contentVariant, $this->constraint);
    }

    public function testValidateNoOrganization()
    {
        /** @var OrganizationInterface $organization */
        $organization = $this->getEntity(Organization::class, ['id' => 1]);

        /** @var Product $attachedEntity */
        $attachedEntity = $this->getEntity(Product::class, ['id' => 2]);
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

        $this->context->expects($this->never())
            ->method($this->anything());

        $this->validator->validate($contentVariant, $this->constraint);
    }

    public function testValidateValid()
    {
        /** @var OrganizationInterface $organization */
        $organization = $this->getEntity(Organization::class, ['id' => 1]);

        /** @var Product $attachedEntity */
        $attachedEntity = $this->getEntity(Product::class, ['id' => 2]);
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

        $this->context->expects($this->never())
            ->method($this->anything());

        $this->validator->validate($contentVariant, $this->constraint);
    }

    public function testValidateValidCollection()
    {
        /** @var OrganizationInterface $organization */
        $organization = $this->getEntity(Organization::class, ['id' => 1]);

        /** @var Product $attachedEntity */
        $attachedEntity = $this->getEntity(Product::class, ['id' => 2]);
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

        $this->context->expects($this->never())
            ->method($this->anything());

        $this->validator->validate($contentVariant, $this->constraint);
    }

    public function testValidateInvalid()
    {
        /** @var OrganizationInterface $organization */
        $organization = $this->getEntity(Organization::class, ['id' => 1]);
        /** @var OrganizationInterface $organization2 */
        $organization2 = $this->getEntity(Organization::class, ['id' => 2]);

        /** @var Product $attachedEntity */
        $attachedEntity = $this->getEntity(Product::class, ['id' => 2]);
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

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects($this->once())
            ->method('addViolation');
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with('oro.webcatalog.scope.empty.message')
            ->willReturn($builder);

        $this->validator->validate($contentVariant, $this->constraint);
    }
}
