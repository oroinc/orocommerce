<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\InventoryBundle\EventListener\CategoryHighlightLowInventoryFormViewListener;
use Oro\Bundle\UIBundle\View\ScrollData;

class CategoryHighlightLowInventoryFormViewListenerTest extends AbstractFallbackFieldsFormViewTest
{
    /** @var CategoryHighlightLowInventoryFormViewListener */
    private $listener;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new CategoryHighlightLowInventoryFormViewListener(
            $this->requestStack,
            $this->doctrine,
            $this->translator,
            $this->fieldAclHelper
        );
    }

    #[\Override]
    protected function callTestMethod(): void
    {
        $this->listener->onCategoryEdit($this->event);
    }

    #[\Override]
    protected function getExpectedScrollData(): array
    {
        return [
            ScrollData::DATA_BLOCKS => [
                1 => [
                    ScrollData::TITLE => 'oro.catalog.sections.default_options.trans',
                    ScrollData::SUB_BLOCKS => [[]]
                ]
            ]
        ];
    }

    #[\Override]
    protected function getEntity(): object
    {
        return new Category();
    }
}
