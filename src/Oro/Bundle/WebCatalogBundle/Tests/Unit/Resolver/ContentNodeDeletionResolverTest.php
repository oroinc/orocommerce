<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Resolver;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\WebCatalogBundle\ContentNodeDeletionChecker\ContentNodeDeletionCheckerInterface;
use Oro\Bundle\WebCatalogBundle\Context\NotDeletableContentNodeResult;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Resolver\ContentNodeDeletionResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContentNodeDeletionResolverTest extends \PHPUnit\Framework\TestCase
{
    private ContentNodeDeletionResolver $contentNodeDeletionResolver;

    private TranslatorInterface $translator;

    private ContentNode $contentNode;

    private ContentNodeDeletionCheckerInterface $checker;

    private NotDeletableContentNodeResult $result;

    #[\Override]
    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->contentNode = $this->getMockBuilder(ContentNode::class)
            ->addMethods(['getTitle'])
            ->onlyMethods(['getChildNodes'])
            ->getMock();

        $this->checker = $this->createMock(ContentNodeDeletionCheckerInterface::class);
        $this->result = $this->createMock(NotDeletableContentNodeResult::class);

        $this->contentNodeDeletionResolver = new ContentNodeDeletionResolver($this->translator, [$this->checker]);
    }

    public function testThatCurrentContentNodeCanNotBeDeleted()
    {
        $this->checker
            ->expects($this->once())
            ->method('check')
            ->with($this->equalTo($this->contentNode))
            ->willReturn($this->result);

        $this->result
            ->expects($this->once())
            ->method('setReferencedContendNode')
            ->with($this->equalTo($this->contentNode));

        $this->result
            ->expects($this->once())
            ->method('getReferencedContendNode')
            ->willReturn($this->contentNode);

        $this->result
            ->expects($this->never())
            ->method('setIsChild');

        $result = $this->contentNodeDeletionResolver->checkOnNotDeletableContentNodeUsingTree($this->contentNode);

        $this->assertInstanceOf(NotDeletableContentNodeResult::class, $result);
        $this->assertEquals($this->result, $result);
    }

    public function testThatChildContentNodeCanNotBeDeleted()
    {
        $childContentNode = $this->createMock(ContentNode::class);

        $this->contentNode
            ->expects($this->once())
            ->method('getChildNodes')
            ->willReturn(new ArrayCollection([$childContentNode]));

        $this->checker
            ->expects($this->exactly(2))
            ->method('check')
            ->withConsecutive(
                [$this->equalTo($this->contentNode)],
                [$childContentNode]
            )
            ->willReturnOnConsecutiveCalls(null, $this->result);

        $this->result
            ->expects($this->once())
            ->method('setReferencedContendNode')
            ->with($this->equalTo($childContentNode));

        $this->result
            ->expects($this->once())
            ->method('getReferencedContendNode')
            ->willReturn($childContentNode);

        $this->result
            ->expects($this->once())
            ->method('setIsChild');

        $result = $this->contentNodeDeletionResolver->checkOnNotDeletableContentNodeUsingTree($this->contentNode);

        $this->assertInstanceOf(NotDeletableContentNodeResult::class, $result);
        $this->assertEquals($this->result, $result);
    }

    public function testThatContentNodeCanBeDeleted()
    {
        $this->contentNode
            ->expects($this->once())
            ->method('getChildNodes')
            ->willReturn(new ArrayCollection([]));

        $this->checker
            ->expects($this->once())
            ->method('check')
            ->with($this->equalTo($this->contentNode))
            ->willReturn(null);

        $this->result
            ->expects($this->never())
            ->method('setReferencedContendNode');

        $this->result
            ->expects($this->never())
            ->method('getReferencedContendNode');

        $this->result
            ->expects($this->never())
            ->method('setIsChild');

        $this->assertNull(
            $this->contentNodeDeletionResolver->checkOnNotDeletableContentNodeUsingTree($this->contentNode)
        );
    }

    public function testWarningMessageForCurrentNotDeletableContentNode()
    {
        $this->result
            ->expects($this->once())
            ->method('getWarningMessageParams')
            ->willReturn(['%nodeName%' => 'Test title']);

        $this->result
            ->expects($this->once())
            ->method('isChild')
            ->willReturn(false);

        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with(
                $this->equalTo('oro.webcatalog.contentnode.denied_deletion_itself'),
                $this->equalTo([
                    '%nodeName%' => 'Test title'
                ]),
                $this->equalTo('validators')
            );

        $this->contentNodeDeletionResolver->getDeletionWarningMessage($this->result);
    }

    public function testWarningMessageForChildNotDeletableContentNode()
    {
        $this->result
            ->expects($this->once())
            ->method('getWarningMessageParams')
            ->willReturn(['%param%' => 'Test title']);

        $this->result
            ->expects($this->once())
            ->method('getReferencedContendNode')
            ->willReturn($this->contentNode);

        $this->contentNode
            ->expects($this->once())
            ->method('getTitle')
            ->willReturn('Parent Test title');

        $this->result
            ->expects($this->once())
            ->method('isChild')
            ->willReturn(true);

        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with(
                $this->equalTo('oro.webcatalog.contentnode.denied_deletion_due_to_child'),
                $this->equalTo([
                    '%nodeName%' => 'Parent Test title',
                    '%param%' => 'Test title',
                ]),
                $this->equalTo('validators')
            );

        $this->contentNodeDeletionResolver->getDeletionWarningMessage($this->result);
    }

    public function testWarningMessageForDeletableContentNode()
    {
        $this->result
            ->expects($this->once())
            ->method('getWarningMessageParams')
            ->willReturn([]);

        $this->assertNull($this->contentNodeDeletionResolver->getDeletionWarningMessage($this->result));
    }
}
