<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\ContentNodeDeletionChecker;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\WebCatalogBundle\ContentNodeDeletionChecker\ContentNodeEntityReferencesChecker;
use Oro\Bundle\WebCatalogBundle\Context\NotDeletableContentNodeResult;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContentNodeEntityReferencesCheckerTest extends \PHPUnit\Framework\TestCase
{
    private ContentNode $contentNode;

    private TranslatorInterface $translator;

    private ContentNodeEntityReferencesChecker $checker;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->contentNode = $this->createMock(ContentNode::class);

        $this->checker = new ContentNodeEntityReferencesChecker(
            $this->translator
        );
    }

    public function testThatContentNodeHasReferencesInCustomMenuUpdate()
    {
        $menuUpdate = $this->createMock(MenuUpdateInterface::class);

        $menuUpdate
            ->expects($this->once())
            ->method('getMenu')
            ->willReturn('Menu Title');

        $menuUpdate
            ->expects($this->once())
            ->method('isCustom')
            ->willReturn(true);

        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with($this->equalTo('oro.commercemenu.menuupdate.menu.label'));

        $this->contentNode
            ->expects($this->once())
            ->method('getReferencedMenuItems')
            ->willReturn(new ArrayCollection([$menuUpdate]));

        $this->contentNode
            ->expects($this->never())
            ->method('getReferencedConsents');

        $result = $this->checker->check($this->contentNode);

        $this->assertInstanceOf(NotDeletableContentNodeResult::class, $result);
    }

    public function testThatContentNodeHasReferencesInRootMenuUpdate()
    {
        $menuUpdate = $this->createMock(MenuUpdateInterface::class);

        $menuUpdate
            ->expects($this->exactly(2))
            ->method('getMenu')
            ->willReturnOnConsecutiveCalls('test_key', 'Menu Title');

        $menuUpdate
            ->expects($this->once())
            ->method('isCustom')
            ->willReturn(false);

        $menuUpdate
            ->expects($this->once())
            ->method('getKey')
            ->willReturn('test_key');

        $menuUpdate
            ->expects($this->once())
            ->method('getParentKey')
            ->willReturn(null);

        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with($this->equalTo('oro.commercemenu.menuupdate.menu.label'));

        $this->contentNode
            ->expects($this->once())
            ->method('getReferencedMenuItems')
            ->willReturn(new ArrayCollection([$menuUpdate]));

        $this->contentNode
            ->expects($this->never())
            ->method('getReferencedConsents');

        $result = $this->checker->check($this->contentNode);

        $this->assertInstanceOf(NotDeletableContentNodeResult::class, $result);
    }

    public function testThatContentNodeHasReferencesInConsents()
    {
        $consent = $this->getMockBuilder(Consent::class)
            ->addMethods(['getName'])
            ->getMock();

        $this->contentNode
            ->expects($this->once())
            ->method('getReferencedMenuItems')
            ->willReturn(new ArrayCollection([]));


        $this->contentNode
            ->expects($this->exactly(2))
            ->method('getReferencedConsents')
            ->willReturn(new ArrayCollection([$consent]));

        $consent
            ->expects($this->once())
            ->method('getName')
            ->willReturn('Consent name');

        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with($this->equalTo('oro.consent.menu.management.label'));

        $result = $this->checker->check($this->contentNode);

        $this->assertInstanceOf(NotDeletableContentNodeResult::class, $result);
    }

    public function testThatEmptyResultReturnedWhenContentNodeDoesNotHaveReferences()
    {
        $this->contentNode
            ->expects($this->once())
            ->method('getReferencedMenuItems')
            ->willReturn(new ArrayCollection([]));


        $this->contentNode
            ->expects($this->once())
            ->method('getReferencedConsents')
            ->willReturn(new ArrayCollection([]));

        $result = $this->checker->check($this->contentNode);

        $this->assertNull($result);
    }
}
