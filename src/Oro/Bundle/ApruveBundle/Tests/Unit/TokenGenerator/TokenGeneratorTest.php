<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Integration;

use Oro\Bundle\ApruveBundle\TokenGenerator\TokenGenerator;

class TokenGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TokenGenerator
     */
    private $generator;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->generator = new TokenGenerator();
    }

    public function testGenerateToken()
    {
        $token = $this->generator->generateToken();

        $this->assertTrue(ctype_print($token), 'is printable');
        $this->assertStringNotMatchesFormat('%S+%S', $token, 'is URI safe');
        $this->assertStringNotMatchesFormat('%S/%S', $token, 'is URI safe');
        $this->assertStringNotMatchesFormat('%S=%S', $token, 'is URI safe');
    }
}
