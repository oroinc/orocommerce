<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;

class ContentNodeFormViewListener extends BaseFormViewListener
{
    public function onContentNodeView(BeforeListRenderEvent $event)
    {
        $this->addViewPageBlock($event);
    }

    public function onContentNodeEdit(BeforeListRenderEvent $event)
    {
        $this->addEditPageBlock($event);
    }

    /**
     * @param ScrollData $scrollData
     * @param string $titleTemplate
     * @param string $descriptionTemplate
     * @param string $keywordsTemplate
     * @param int $priority
     */
    protected function addSEOBlock(
        ScrollData $scrollData,
        $titleTemplate,
        $descriptionTemplate,
        $keywordsTemplate,
        $priority = 10
    ) {
        // Set priorities to existing blocks to be able to pass new block in the middle
        $data = $scrollData->getData();
        if (isset($data[ScrollData::DATA_BLOCKS]) && count($data[ScrollData::DATA_BLOCKS]) > 0) {
            foreach ($data[ScrollData::DATA_BLOCKS] as $i => &$block) {
                if (!array_key_exists(ScrollData::PRIORITY, $block)) {
                    $block[ScrollData::PRIORITY] = $i * 10 + 1;
                }
            }
        }
        $scrollData->setData($data);

        parent::addSEOBlock($scrollData, $titleTemplate, $descriptionTemplate, $keywordsTemplate);
    }

    /**
     * @return string
     */
    public function getMetaFieldLabelPrefix()
    {
        return 'oro.webcatalog.contentnode';
    }
}
