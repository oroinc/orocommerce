<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

class ContentNodeFormViewListener extends BaseFormViewListener
{
    /**
     * @param BeforeListRenderEvent $event
     */
    public function onContentNodeView(BeforeListRenderEvent $event)
    {
        $this->addViewPageBlock($event, ContentNode::class);
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onContentNodeEdit(BeforeListRenderEvent $event)
    {
        $this->addEditPageBlock($event);
    }

    /**
     * @param ScrollData $scrollData
     * @param string $descriptionTemplate
     * @param string $keywordsTemplate
     */
    protected function addSEOBlock(ScrollData $scrollData, $descriptionTemplate, $keywordsTemplate)
    {
        // Set priorities to existing blocks to be able to pass new block in the middle
        $data = $scrollData->getData();
        if (count($data[ScrollData::DATA_BLOCKS]) > 0) {
            foreach ($data[ScrollData::DATA_BLOCKS] as $i => &$block) {
                if (!array_key_exists(ScrollData::PRIORITY, $block)) {
                    $block[ScrollData::PRIORITY] = $i * 10 + 1;
                }
            }
        }
        $scrollData->setData($data);

        parent::addSEOBlock($scrollData, $descriptionTemplate, $keywordsTemplate);
    }

    /**
     * @return string
     */
    public function getMetaFieldLabelPrefix()
    {
        return 'oro.webcatalog.contentnode';
    }
}
