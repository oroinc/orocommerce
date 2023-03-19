<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Entity;

use Oro\Bundle\RedirectBundle\Entity\Redirect;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class RedirectTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(new Redirect(), [
            ['id', 42],
            ['from', 'url/from'],
            ['fromPrototype', null],
            ['fromPrototype', 'from-prototype'],
            ['to', 'url/to'],
            ['toPrototype', null],
            ['toPrototype', 'to-prototype'],
            ['type', Redirect::MOVED_PERMANENTLY],
            ['slug', new Slug()]
        ]);

        $this->assertPropertyCollections(new Slug(), [
            ['scopes', new Scope()]
        ]);
    }

    public function testSetFromHash()
    {
        $from = 'test';

        $redirect = new Redirect();
        $redirect->setFrom($from);

        self::assertEquals(md5($from), ReflectionUtil::getPropertyValue($redirect, 'fromHash'));
    }
}
