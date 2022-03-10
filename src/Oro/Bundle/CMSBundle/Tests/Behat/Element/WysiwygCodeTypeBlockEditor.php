<?php

namespace Oro\Bundle\CMSBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class WysiwygCodeTypeBlockEditor extends Element
{
    /**
     * @param string $value
     */
    public function setValue($value): void
    {
        if (!is_string($value)) {
            throw new \InvalidArgumentException('CodeMirror value should be only string.');
        }

        $this->session->executeScript(
            sprintf(
                '(function(){
                    document.querySelector(".CodeMirror").CodeMirror.setValue(`%s`)
                })()',
                $value
            )
        );
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->session->evaluateScript(
            '(function(){
                return document.querySelector(".CodeMirror").CodeMirror.getValue()
            })()'
        );
    }
}
