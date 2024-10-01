<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener\WebsiteSearchTerm;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\EventListener\WebsiteSearchTerm\AddCategoryToSearchTermViewPageListener;
use Oro\Bundle\CatalogBundle\Tests\Unit\Stub\SearchTermStub;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

class AddCategoryToSearchTermViewPageListenerTest extends TestCase
{
    private Environment|MockObject $environment;

    private AddCategoryToSearchTermViewPageListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->environment = $this->createMock(Environment::class);

        $this->listener = new AddCategoryToSearchTermViewPageListener();
    }

    public function testOnEntityViewWhenBlankSearchTerm(): void
    {
        $scrollData = new ScrollData();
        $event = new BeforeListRenderEvent($this->environment, $scrollData, new SearchTerm());

        $this->listener->onEntityView($event);

        self::assertEquals([], $scrollData->getData());
    }

    public function testOnEntityViewWhenNotRedirectCategory(): void
    {
        $scrollData = new ScrollData();
        $searchTerm = (new SearchTerm())->setActionType('redirect');
        $event = new BeforeListRenderEvent($this->environment, $scrollData, $searchTerm);

        $this->listener->onEntityView($event);

        self::assertEquals([], $scrollData->getData());
    }

    public function testOnEntityViewWhenNotRedirect(): void
    {
        $scrollData = new ScrollData();
        $searchTerm = (new SearchTerm())->setActionType('modify')->setRedirectActionType('category');
        $event = new BeforeListRenderEvent($this->environment, $scrollData, $searchTerm);

        $this->listener->onEntityView($event);

        self::assertEquals([], $scrollData->getData());
    }

    public function testOnEntityViewWhenEmptyScrollData(): void
    {
        $scrollData = new ScrollData();
        $category = new Category();
        $searchTerm = (new SearchTermStub())
            ->setActionType('redirect')
            ->setRedirectActionType('category')
            ->setRedirectCategory($category);
        $event = new BeforeListRenderEvent($this->environment, $scrollData, $searchTerm);

        $categoryData = 'category data';
        $this->environment
            ->expects(self::once())
            ->method('render')
            ->with(
                '@OroCatalog/SearchTerm/redirect_category_field.html.twig',
                ['entity' => $searchTerm->getRedirectCategory()]
            )
            ->willReturn($categoryData);

        $this->listener->onEntityView($event);

        self::assertEquals(
            [
                ScrollData::DATA_BLOCKS => [
                    'action' => [
                        ScrollData::SUB_BLOCKS => [
                            [
                                ScrollData::DATA => [
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

    public function testOnEntityViewWhenNotEmptyScrollData(): void
    {
        $scrollData = new ScrollData([
            ScrollData::DATA_BLOCKS => [
                'action' => [
                    ScrollData::SUB_BLOCKS => [
                        [
                            ScrollData::DATA => ['sampleField' => 'sample data'],
                        ],
                    ],
                ],
            ],
        ]);
        $category = new Category();
        $searchTerm = (new SearchTermStub())
            ->setActionType('redirect')
            ->setRedirectActionType('category')
            ->setRedirectCategory($category);
        $event = new BeforeListRenderEvent($this->environment, $scrollData, $searchTerm);

        $categoryData = 'category data';
        $this->environment
            ->expects(self::once())
            ->method('render')
            ->with(
                '@OroCatalog/SearchTerm/redirect_category_field.html.twig',
                ['entity' => $searchTerm->getRedirectCategory()]
            )
            ->willReturn($categoryData);

        $this->listener->onEntityView($event);

        self::assertEquals(
            [
                ScrollData::DATA_BLOCKS => [
                    'action' => [
                        ScrollData::SUB_BLOCKS => [
                            [
                                ScrollData::DATA => [
                                    'sampleField' => 'sample data',
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
