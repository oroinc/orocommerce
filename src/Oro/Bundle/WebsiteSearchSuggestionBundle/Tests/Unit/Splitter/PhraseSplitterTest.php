<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Tests\Unit\Splitter;

use Oro\Bundle\WebsiteSearchSuggestionBundle\Splitter\PhraseSplitter;

class PhraseSplitterTest extends \PHPUnit\Framework\TestCase
{
    public function testThatSplitterWorks()
    {
        $splitter = new PhraseSplitter();

        self::assertEquals([
            'client',
            'client credit',
            'client credit card',
            'client card',
            'credit',
            'credit card',
            'card',
        ], $splitter->split('Client, Credit Card'));
    }
}
