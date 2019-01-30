<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Model;

use Oro\Bundle\RedirectBundle\Model\TextSlugPrototypeWithRedirect;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class TextSlugPrototypeWithRedirectTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['createRedirect', false, false],
            ['textSlugPrototype', 'text', false],
        ];

        $text = 'text';
        $this->assertPropertyAccessors(new TextSlugPrototypeWithRedirect($text), $properties);
    }
}
