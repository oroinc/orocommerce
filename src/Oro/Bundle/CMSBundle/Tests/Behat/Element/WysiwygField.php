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
        $this->session->wait(300);
        $this->session->executeScript(
            sprintf(
                '(function(){
                    $("#%s")
                        .trigger("wysiwyg:disable")
                        .val("%s")
                        .trigger("change")
                        .trigger("wysiwyg:enable");
                })()',
                $this->getAttribute('id'),
                $value
            )
        );
        $this->session->wait(300);
    }
}
