<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Form\Storage;

use Oro\Bundle\RedirectBundle\Form\Storage\RedirectStorage;
use Oro\Bundle\RedirectBundle\Model\PrefixWithRedirect;

class RedirectStorageTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var RedirectStorage
     */
    protected $redirectStorage;

    protected function setUp(): void
    {
        $this->redirectStorage = new RedirectStorage();
    }

    public function testStorage()
    {
        $keyOne = 'key.one';
        $keyTwo = 'key.two';

        $prefixOne = new PrefixWithRedirect();
        $prefixOne->setPrefix('some-prefix');
        $prefixOne->setCreateRedirect(true);

        $prefixTwo = new PrefixWithRedirect();
        $prefixTwo->setPrefix('some-prefix');
        $prefixTwo->setCreateRedirect(true);

        $this->redirectStorage->addPrefix($keyOne, $prefixOne);
        $this->redirectStorage->addPrefix($keyTwo, $prefixTwo);
        $this->assertEquals($prefixOne, $this->redirectStorage->getPrefixByKey($keyOne));
        $this->assertEquals($prefixTwo, $this->redirectStorage->getPrefixByKey($keyTwo));
        $this->assertEquals(null, $this->redirectStorage->getPrefixByKey('not_existing_key'));
    }
}
