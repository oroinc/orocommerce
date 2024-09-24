<?php

namespace Oro\Bundle\PricingBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

/**
 * This class provides the ability to manage CodeMirror field
 */
class CodeMirrorField extends Element
{
    #[\Override]
    public function setValue($value)
    {
        $this->session->executeScript(
            sprintf(
                '(function(){
                    $("#%s").val(`%s`).trigger("change");
                })()',
                $this->getAttribute('id'),
                $value
            )
        );
    }
}
