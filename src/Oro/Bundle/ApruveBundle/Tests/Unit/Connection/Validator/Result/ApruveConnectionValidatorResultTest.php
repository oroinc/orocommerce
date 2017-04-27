<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Connection\Validator\Result;

use Oro\Bundle\ApruveBundle\Connection\Validator\Result\ApruveConnectionValidatorResult;

class ApruveConnectionValidatorResultTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    private $parameters;

    /**
     * @var ApruveConnectionValidatorResult
     */
    private $connectionValidationResult;

    protected function setUp()
    {
        $this->parameters = [
            'status' => true,
            'error_severity' => 'error_severity',
            'error_message' => 'Wrong api key',
        ];
        $this->connectionValidationResult = new ApruveConnectionValidatorResult($this->parameters);
    }

    public function testGetters()
    {
        static::assertEquals($this->parameters['status'], $this->connectionValidationResult->getStatus());
        static::assertEquals(
            $this->parameters['error_severity'],
            $this->connectionValidationResult->getErrorSeverity()
        );
        static::assertEquals($this->parameters['error_message'], $this->connectionValidationResult->getErrorMessage());
    }
}
