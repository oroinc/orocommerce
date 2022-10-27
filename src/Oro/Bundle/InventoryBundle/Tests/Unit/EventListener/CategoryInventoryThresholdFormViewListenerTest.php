<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\InventoryBundle\EventListener\CategoryInventoryThresholdFormViewListener;
use Oro\Bundle\UIBundle\View\ScrollData;

class CategoryInventoryThresholdFormViewListenerTest extends AbstractFallbackFieldsFormViewTest
{
    /** @var CategoryInventoryThresholdFormViewListener */
    protected $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new CategoryInventoryThresholdFormViewListener(
            $this->requestStack,
            $this->doctrine,
            $this->translator
        );
    }

    protected function tearDown(): void
    {
        unset($this->listener);

        parent::tearDown();
    }

    /**
     * {@inheritdoc}
     */
    protected function callTestMethod()
    {
        $this->listener->onCategoryEdit($this->event);
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedScrollData()
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

    /**
     * @return Category
     */
    protected function getEntity()
    {
        return new Category();
    }
}
