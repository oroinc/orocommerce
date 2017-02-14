<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\RedirectBundle\Model\SlugPrototypesWithRedirect;
use Oro\Bundle\RedirectBundle\Model\TextSlugPrototypeWithRedirect;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class TextSlugPrototypeWithRedirectTest extends \PHPUnit_Framework_TestCase
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
