<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener\WebsiteSearchTerm;

use Oro\Bundle\CatalogBundle\EventListener\WebsiteSearchTerm\AddCategoryToSearchTermEditPageListener;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormView;
use Twig\Environment;

class AddCategoryToSearchTermEditPageListenerTest extends TestCase
{
    private Environment|MockObject $environment;

    private AddCategoryToSearchTermEditPageListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->environment = $this->createMock(Environment::class);

        $this->listener = new AddCategoryToSearchTermEditPageListener();
    }

    public function testOnEntityEditWhenEmptyScrollData(): void
    {
        $scrollData = new ScrollData();
        $event = new BeforeListRenderEvent($this->environment, $scrollData, new SearchTerm());

        $this->listener->onEntityEdit($event);

        self::assertEquals([], $scrollData->getData());
    }

    public function testOnEntityEditWhenNoRedirect301(): void
    {
        $scrollData = new ScrollData([
            ScrollData::DATA_BLOCKS => ['action' => []],
        ]);
        $event = new BeforeListRenderEvent($this->environment, $scrollData, new SearchTerm());

        $this->listener->onEntityEdit($event);

        self::assertEquals([ScrollData::DATA_BLOCKS => ['action' => []]], $scrollData->getData());
    }

    public function testOnEntityEdit(): void
    {
        $scrollData = new ScrollData([
            ScrollData::DATA_BLOCKS => [
                'action' => [
                    ScrollData::SUB_BLOCKS => [
                        [
                            ScrollData::DATA => ['redirect301' => 'sample data'],
                        ],
                    ],
                ],
            ],
        ]);
        $formView = $this->createMock(FormView::class);
        $event = new BeforeListRenderEvent($this->environment, $scrollData, new SearchTerm(), $formView);

        $categoryData = 'category data';
        $this->environment
            ->expects(self::once())
            ->method('render')
            ->with(
                '@OroCatalog/SearchTerm/redirect_category_form.html.twig',
                ['form' => $formView]
            )
            ->willReturn($categoryData);

        $this->listener->onEntityEdit($event);

        self::assertEquals(
            [
                ScrollData::DATA_BLOCKS => [
                    'action' => [
                        ScrollData::SUB_BLOCKS => [
                            [
                                ScrollData::DATA => [
                                    'redirect301' => 'sample data',
                                    'redirectCategory' => $categoryData,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            $scrollData->getData()
        );
    }
}
