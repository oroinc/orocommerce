<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Model;

use Oro\Bundle\DPDBundle\Model\DPDRequest;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class DPDRequestTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /**
     * @var DPDRequest
     */
    protected $model;

    protected function setUp()
    {
        $this->model = new DPDRequest();
    }

    protected function tearDown()
    {
        unset($this->model);
    }

    public function testAccessors()
    {
    }
}
