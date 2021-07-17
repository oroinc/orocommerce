<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Validator\Constraint;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Validator\Constraint\UniqueSlugPrototype;
use Oro\Bundle\WebCatalogBundle\Validator\Constraint\UniqueSlugPrototypeValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class UniqueSlugPrototypeTest extends ConstraintValidatorTestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->constraint = new UniqueSlugPrototype();
        $this->context = $this->createContext();
        $this->validator = $this->createValidator();
        $this->validator->initialize($this->context);
    }

    protected function createValidator()
    {
        $this->registry = $this->createMock(ManagerRegistry::class);

        return new UniqueSlugPrototypeValidator($this->registry);
    }

    public function testValidateNull()
    {
        $value = null;

        $this->registry->expects($this->never())
            ->method($this->anything());

        $this->validator->validate($value, $this->constraint);
        $this->assertNoViolation();
    }

    public function testValidateRootNode()
    {
        /** @var ContentNode $value */
        $value = $this->createContentNode(1);
        $slugPrototype = (new LocalizedFallbackValue())->setString('test1');
        $value->addSlugPrototype($slugPrototype);

        $this->registry->expects($this->never())
            ->method($this->anything());

        $this->validator->validate($value, $this->constraint);
        $this->assertNoViolation();
    }

    public function testValidateValidForPersistedNode()
    {
        /** @var ContentNode $parentNode */
        $parentNode = $this->createContentNode(1);
        $slugPrototype1 = (new LocalizedFallbackValue())->setString('test');
        $parentNode->addSlugPrototype($slugPrototype1);

        /** @var ContentNode $firstChild */
        $firstChild = $this->createContentNode(2);
        $slugPrototype2 = (new LocalizedFallbackValue())->setString('test_child');
        $firstChild->addSlugPrototype($slugPrototype2);
        $firstChild->setParentNode($parentNode);

        /** @var ContentNode $value */
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

        $this->validator->validate($value, $this->constraint);
        $this->assertNoViolation();
    }

    public function testValidateValidForNewNode()
    {
        /** @var ContentNode $parentNode */
        $parentNode = $this->createContentNode(1);
        $slugPrototype1 = (new LocalizedFallbackValue())->setString('test');
        $parentNode->addSlugPrototype($slugPrototype1);

        /** @var ContentNode $firstChild */
        $firstChild = $this->createContentNode(2);
        $slugPrototype2 = (new LocalizedFallbackValue())->setString('test_child');
        $firstChild->addSlugPrototype($slugPrototype2);
        $firstChild->setParentNode($parentNode);

        /** @var ContentNode $value */
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

        $this->validator->validate($value, $this->constraint);
        $this->assertNoViolation();
    }

    public function testValidateInvalid()
    {
        /** @var ContentNode $parentNode */
        $parentNode = $this->createContentNode(1);
        $slugPrototype1 = (new LocalizedFallbackValue())->setString('test');
        $parentNode->addSlugPrototype($slugPrototype1);

        /** @var ContentNode $firstChild */
        $firstChild = $this->createContentNode(2);
        $slugPrototype2 = (new LocalizedFallbackValue())->setString('test1');
        $firstChild->addSlugPrototype($slugPrototype2);
        $firstChild->setParentNode($parentNode);

        /** @var ContentNode $value */
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

        $this->validator->validate($value, $this->constraint);

        $this->buildViolation('oro.webcatalog.contentnode.slug_prototype.unique.message')
            ->atPath('property.path.slugPrototypes[0]')
            ->assertRaised();
    }

    private function createContentNode(int $id): ContentNode
    {
        $node = new ContentNode();
        $reflectionClass = new \ReflectionClass($node);
        $method = $reflectionClass->getProperty('id');
        $method->setAccessible(true);
        $method->setValue($node, $id);

        return $node;
    }
}
