<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Entity;

use Oro\Bundle\RedirectBundle\Entity\Redirect;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class RedirectTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(new Redirect(), [
            ['id', 42],
            ['from', 'url/from'],
            ['to', 'url/to'],
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

        $this->assertAttributeEquals(md5($from), 'fromHash', $redirect);
    }
}
