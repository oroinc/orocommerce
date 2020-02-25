<?php

namespace Oro\Bundle\CMSBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

/**
 * This class provides the ability to manage wysiwyg field
 */
class WysiwygField extends Element
{
    /**
     * {@inheritdoc}
     */
    public function setValue($value)
    {
        $this->session->executeScript(
            sprintf(
                '(function(){
                    $("#%s").val("%s")
                        .trigger("change")
                        .trigger("wysiwyg:disable")
                        .trigger("wysiwyg:enable");
                })()',
                $this->getAttribute('id'),
                $value
            )
        );
    }
}
