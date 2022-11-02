<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Validator\Constraint;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Validator\Constraint\UniqueSlugPrototype;
use Oro\Bundle\WebCatalogBundle\Validator\Constraint\UniqueSlugPrototypeValidator;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class UniqueSlugPrototypeValidatorTest extends ConstraintValidatorTestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        parent::setUp();
    }

    protected function createValidator(): UniqueSlugPrototypeValidator
    {
        return new UniqueSlugPrototypeValidator($this->registry);
    }

    private function createContentNode(int $id): ContentNode
    {
        $node = new ContentNode();
        ReflectionUtil::setId($node, $id);

        return $node;
    }

    public function testValidateNull()
    {
        $this->registry->expects($this->never())
            ->method($this->anything());

        $constraint = new UniqueSlugPrototype();
        $this->validator->validate(null, $constraint);
        $this->assertNoViolation();
    }

    public function testValidateRootNode()
    {
        $value = $this->createContentNode(1);
        $slugPrototype = (new LocalizedFallbackValue())->setString('test1');
        $value->addSlugPrototype($slugPrototype);

        $this->registry->expects($this->never())
            ->method($this->anything());

        $constraint = new UniqueSlugPrototype();
        $this->validator->validate($value, $constraint);
        $this->assertNoViolation();
    }

    public function testValidateValidForPersistedNode()
    {
        $parentNode = $this->createContentNode(1);
        $slugPrototype1 = (new LocalizedFallbackValue())->setString('test');
        $parentNode->addSlugPrototype($slugPrototype1);

        $firstChild = $this->createContentNode(2);
        $slugPrototype2 = (new LocalizedFallbackValue())->setString('test_child');
        $firstChild->addSlugPrototype($slugPrototype2);
        $firstChild->setParentNode($parentNode);

        $value = $this->createContentNode(3);
        $slugPrototype2 = (new LocalizedFallbackValue())->setString('test1');
        $value->addSlugPrototype($slugPrototype2);
        $value->setParentNode($parentNode);

        $repo = $this->createMock(ContentNodeRepository::class);
        $repo->expects($this->once())
            ->method('getSlugPrototypesByParent')
            ->with($parentNode, $value)
            ->willReturn(['test_child']);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(ContentNode::class)
            ->willReturn($repo);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ContentNode::class)
            ->willReturn($em);

        $constraint = new UniqueSlugPrototype();
        $this->validator->validate($value, $constraint);
        $this->assertNoViolation();
    }

    public function testValidateValidForNewNode()
    {
        $parentNode = $this->createContentNode(1);
        $slugPrototype1 = (new LocalizedFallbackValue())->setString('test');
        $parentNode->addSlugPrototype($slugPrototype1);

        $firstChild = $this->createContentNode(2);
        $slugPrototype2 = (new LocalizedFallbackValue())->setString('test_child');
        $firstChild->addSlugPrototype($slugPrototype2);
        $firstChild->setParentNode($parentNode);

        $value = new ContentNode();
        $slugPrototype2 = (new LocalizedFallbackValue())->setString('test1');
        $value->addSlugPrototype($slugPrototype2);
        $value->setParentNode($parentNode);

        $repo = $this->createMock(ContentNodeRepository::class);
        $repo->expects($this->once())
            ->method('getSlugPrototypesByParent')
            ->with($parentNode, null)
            ->willReturn(['test_child']);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(ContentNode::class)
            ->willReturn($repo);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ContentNode::class)
            ->willReturn($em);

        $constraint = new UniqueSlugPrototype();
        $this->validator->validate($value, $constraint);
        $this->assertNoViolation();
    }

    public function testValidateInvalid()
    {
        $parentNode = $this->createContentNode(1);
        $slugPrototype1 = (new LocalizedFallbackValue())->setString('test');
        $parentNode->addSlugPrototype($slugPrototype1);

        $firstChild = $this->createContentNode(2);
        $slugPrototype2 = (new LocalizedFallbackValue())->setString('test1');
        $firstChild->addSlugPrototype($slugPrototype2);
        $firstChild->setParentNode($parentNode);

        $value = new ContentNode();
        $slugPrototype2 = (new LocalizedFallbackValue())->setString('Test1');
        $value->addSlugPrototype($slugPrototype2);
        $value->setParentNode($parentNode);

        $repo = $this->createMock(ContentNodeRepository::class);
        $repo->expects($this->once())
            ->method('getSlugPrototypesByParent')
            ->with($parentNode, null)
            ->willReturn(['test1']);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(ContentNode::class)
            ->willReturn($repo);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ContentNode::class)
            ->willReturn($em);

        $constraint = new UniqueSlugPrototype();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.slugPrototypes[0]')
            ->assertRaised();
    }
}
