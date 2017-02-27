<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Connection\Validator\Result;

use Oro\Bundle\UPSBundle\Connection\Validator\Result\UpsConnectionValidatorResult;

class UpsConnectionValidatorResultTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    protected $parameters;

    /**
     * @var UpsConnectionValidatorResult
     */
    protected $connectionValidationResult;

    protected function setUp()
    {
        $this->parameters = [
            'status' => true,
            'error_severity' => UpsConnectionValidatorResult::WARNING_SEVERITY,
            'error_message' => 'Wrong Password'
        ];
        $this->connectionValidationResult = new UpsConnectionValidatorResult($this->parameters);
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
