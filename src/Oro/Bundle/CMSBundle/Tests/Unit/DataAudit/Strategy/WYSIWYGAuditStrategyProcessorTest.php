<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DataAudit\Strategy;

use Oro\Bundle\CMSBundle\DataAudit\Strategy\WYSIWYGAuditStrategyProcessor;
use Oro\Bundle\DataAuditBundle\Strategy\EntityAuditStrategyDelegateProcessor;
use Oro\Bundle\DataAuditBundle\Strategy\Processor\EntityAuditStrategyProcessorInterface;
use Oro\Bundle\ProductBundle\Entity\ProductDescription;
use Oro\Bundle\ProductBundle\Entity\ProductShortDescription;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WYSIWYGAuditStrategyProcessorTest extends TestCase
{
    use EntityTrait;

    protected EntityAuditStrategyDelegateProcessor|MockObject $innerStrategy;

    protected WYSIWYGAuditStrategyProcessor $strategy;

    protected function setUp(): void
    {
        $this->innerStrategy = $this->createMock(EntityAuditStrategyProcessorInterface::class);

        $this->strategy = new WYSIWYGAuditStrategyProcessor($this->innerStrategy);
    }

    public function testProcessEntityAssociationsFromCollectionWoWygiwysField()
    {
        /** @var ProductShortDescription $productShortDesc */
        $productShortDesc = $this->getEntity(ProductShortDescription::class, ['id' => 123]);

        $sourceEntityData = [
            'entity_class' => ProductShortDescription::class,
            'entity_id' => $productShortDesc->getId(),
            'change_set' => [
                'text' => [
                    'This medical identifications tag is a beautiful and practical way to wear your medical information.
                     Measuring 1" x 3/4", the tag is coated with a fine, Rhodium finish,
                      helping the tag stay shiny and resistant to tarnish.The tag can be engraved on the back.',
                    'This medical identifications tag is a beautiful and practical way to wear your medical information.
                     Measuring 1" x 3/4", the tag is coated with a fine, Rhodium finish,
                      helping the tag stay shiny and resistant to tarnish. The tag can be engraved on the back.'
                ]
            ]
        ];

        $this->innerStrategy->expects($this->once())
            ->method('processInverseCollections')
            ->willReturn([]);

        $this->strategy->processInverseCollections($sourceEntityData);
    }

    public function testProcessEntityAssociationsFromCollectionHTHMLAttrDisorder()
    {
        /** @var ProductDescription $productDesc */
        $productDesc = $this->getEntity(ProductDescription::class, ['id' => 234]);

        $sourceEntityData = [
            'entity_class' => ProductDescription::class,
            'entity_id' => $productDesc->getId(),
            'change_set' => [
                'wysiwyg' => [
                    '<div id="isolation-scope-na2n4hknytju2hg7m0ll24168"><p id="333" class="product-view-desc">
                    This medical identifications tag is a beautiful and practical way to wear your medical information.
                    Measuring 1" x 3/4", the tag is coated with a fine, Rhodium finish,
                    helping the tag stay shiny and resistant to tarnish. The tag can be engraved on the back.
                    </p></div>',
                    '<div id="isolation-scope-zz9k0wbys81pq0hzyrrg32384"><p class="product-view-desc" id="333">
                    This medical identifications tag is a beautiful and practical way to wear your medical information.
                    Measuring 1" x 3/4", the tag is coated with a fine, Rhodium finish,
                    helping the tag stay shiny and resistant to tarnish. The tag can be engraved on the back.
                    </p></div>'
                ]
            ]
        ];

        $this->innerStrategy->expects($this->never())
            ->method('processInverseCollections');

        $this->strategy->processInverseCollections($sourceEntityData);
    }

    public function testProcessEntityAssociationsFromCollectionHTMLDiffLength()
    {
        /** @var ProductDescription $productDesc */
        $productDesc = $this->getEntity(ProductDescription::class, ['id' => 234]);

        $sourceEntityData = [
            'entity_class' => ProductDescription::class,
            'entity_id' => $productDesc->getId(),
            'change_set' => [
                'wysiwyg' => [
                    '<div id="isolation-scope-na2n4hknytju2hg7m0ll24168"><p class="product-view-desc" id="333">
                    This medical identifications tag is a beautiful and practical way to wear your medical information.
                    Measuring 1" x 3/4", the tag is coated with a fine, Rhodium finish,
                    helping the tag stay shiny and resistant to tarnish.
                    </p></div>',
                    '<div id="isolation-scope-zz9k0wbys81pq0hzyrrg32384"><p class="product-view-desc" id="333">
                    This medical identifications tag is a beautiful and practical way to wear your medical information.
                    Measuring 1" x 3/4", the tag is coated with a fine, Rhodium finish,
                    helping the tag stay shiny and resistant to tarnish. The tag can be engraved on the back.
                    </p></div>'
                ]
            ]
        ];

        $this->innerStrategy->expects($this->once())
            ->method('processInverseCollections')
            ->willReturn([]);

        $this->strategy->processInverseCollections($sourceEntityData);
    }

    public function testProcessEntityAssociationsFromCollectionHTMLDiffContents()
    {
        /** @var ProductDescription $productDesc */
        $productDesc = $this->getEntity(ProductDescription::class, ['id' => 234]);

        $sourceEntityData = [
            'entity_class' => ProductDescription::class,
            'entity_id' => $productDesc->getId(),
            'change_set' => [
                'wysiwyg' => [
                    '<div id="isolation-scope-na2n4hknytju2hg7m0ll24168"><p id="345" class="product-view-desc">
                    This medical identifications tag is a beautiful and practical way to wear your medical information.
                    Measuring 1" x 3/4", the tag is coated with a fine, Rhodium finish,
                    helping the tag stay shiny and resistant to tarnish. The tag can be engraved on the back.
                    </p></div>',
                    '<div id="isolation-scope-zz9k0wbys81pq0hzyrrg32384"><p class="product-view-desc" id="333">
                    This medical identifications tag is a beautiful and practical way to wear your medical information.
                    Measuring 1" x 3/4", the tag is coated with a fine, Rhodium finish,
                    helping the tag stay shiny and resistant to tarnish. The tag can be engraved on the back.
                    </p></div>'
                ]
            ]
        ];

        $this->innerStrategy->expects($this->once())
            ->method('processInverseCollections')
            ->willReturn([]);

        $this->strategy->processInverseCollections($sourceEntityData);
    }
}
