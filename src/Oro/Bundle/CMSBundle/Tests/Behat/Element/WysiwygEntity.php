<?php

namespace Oro\Bundle\CMSBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

/**
 * This class provides the ability to manage wysiwyg entity
 */
class WysiwygEntity extends Element
{
    public function getIframe()
    {
        return $this->getParent()->find('css', 'iframe.gjs-frame');
    }

    /**
     * Activate edit mode for editor BlockType
     * @param String $blockTypeSelector
     */
    public function teaseBlockType($type, $blockTypeSelector)
    {
        $iframe = $this->getIframe();
        if ($iframe && $iframe->isVisible()) {
            $this->getDriver()->switchToIFrameByElement($iframe);
            $iframeBody = $this->getSession()->getPage()->find('css', 'body');

            $blockType = $iframeBody->find($type, $blockTypeSelector);

            if ($blockType && $blockType->isVisible()) {
                $blockType->click();
                $blockType->doubleClick();
                $blockType->getParent()->click();
            }
            $this->getDriver()->switchToWindow();
        }
    }

    /**
     * Activate edit mode for editor TextBlockType
     */
    public function teaseTextBlockType()
    {
        $this->teaseBlockType('css', 'div[data-gjs-type="text"]');
    }
}
