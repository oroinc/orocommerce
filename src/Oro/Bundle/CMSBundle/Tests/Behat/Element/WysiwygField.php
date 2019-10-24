<?php

namespace Oro\Bundle\CMSBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class WysiwygField extends Element
{
    /**
     * {@inheritdoc}
     */
    public function setValue($value)
    {
        $this->session->executeScript(
            sprintf('(function(){$("#%s").val("%s");})()', $this->getAttribute('id'), $value)
        );
    }
}
