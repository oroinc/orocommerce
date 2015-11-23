<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Command\Stub;

use Symfony\Component\Console\Output\Output;

class OutputStub extends Output
{
    /**
     * @var array
     */
    public $messages = array();

    protected function doWrite($message, $newline)
    {
        $this->messages[] = $message;
    }
}
