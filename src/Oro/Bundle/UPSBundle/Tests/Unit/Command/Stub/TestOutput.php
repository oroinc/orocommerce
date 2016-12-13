<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Command\Stub;

use Symfony\Component\Console\Output\Output;

class TestOutput extends Output
{
    /**
     * @var array
     */
    public $messages = array();

    /**
     * {@inheritdoc}
     */
    protected function doWrite($message, $newline)
    {
        $this->messages[] = $message;
    }
}
