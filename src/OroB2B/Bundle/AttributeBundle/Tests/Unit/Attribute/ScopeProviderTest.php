<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Attribute;

use OroB2B\Bundle\AttributeBundle\Attribute\ScopeProvider;

class ScopeProviderTest extends \PHPUnit_Framework_TestCase
{

    public function testGetChoices()
    {
        /** @var \Symfony\Component\Translation\TranslatorInterface $translator */
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $scopeProvider = new ScopeProvider($translator);
        $this->assertInternalType('array', $scopeProvider->getChoices());
    }

    public function testGetScope()
    {
        /** @var \Symfony\Component\Translation\TranslatorInterface $translator */
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $scopeProvider = new ScopeProvider($translator);
        $this->assertInternalType('array', $scopeProvider->getScopes());
    }
}
