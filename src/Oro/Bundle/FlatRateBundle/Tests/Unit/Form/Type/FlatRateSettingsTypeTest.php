<?php

namespace Oro\Bundle\FlatRateBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FlatRateBundle\Form\Type\FlatRateSettingsType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class FlatRateSettingsTypeTest extends FormIntegrationTestCase
{
    /** @var FlatRateSettingsType */
    private $formType;

    protected function setUp()
    {
        $this->formType = new FlatRateSettingsType();
    }

    public function testGetBlockPrefixReturnsString()
    {
        static::assertTrue(is_string($this->formType->getBlockPrefix()));
    }
}
