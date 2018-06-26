<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\RedirectBundle\Model\SlugPrototypesWithRedirect;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class SlugPrototypesWithRedirectTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['createRedirect', false, false],
        ];

        $this->assertPropertyAccessors(new SlugPrototypesWithRedirect(new ArrayCollection()), $properties);

        $this->assertPropertyCollections(new SlugPrototypesWithRedirect(new ArrayCollection()), [
            ['slugPrototypes', new LocalizedFallbackValue()],
        ]);
    }
}
