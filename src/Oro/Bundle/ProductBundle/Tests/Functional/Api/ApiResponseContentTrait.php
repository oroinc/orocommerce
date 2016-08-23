<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api;

trait ApiResponseContentTrait
{
    /**
     * @param array $expected
     * @param array $content
     */
    protected function assertIsContained(array $expected, array $content)
    {
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $content);
            if (is_array($value)) {
                $this->assertIsContained($value, $content[$key]);
            } else {
                $this->assertEquals($value, $content[$key]);
            }
        }
    }
}
