<?php

namespace OroB2B\Bundle\FrontendBundle\Tests\Unit\Request;

use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\FrontendBundle\Request\FrontendHelper;

class FrontendHelperTest extends \PHPUnit_Framework_TestCase
{
    const BACKEND_PREFIX = '/admin';

    /**
     * @param string $path
     * @param bool $isFrontend
     * @dataProvider isFrontendRequestDataProvider
     */
    public function testIsFrontendRequest($path, $isFrontend)
    {
        $helper = new FrontendHelper(self::BACKEND_PREFIX);
        $this->assertSame($isFrontend, $helper->isFrontendRequest(Request::create($path)));
    }

    /**
     * @return array
     */
    public function isFrontendRequestDataProvider()
    {
        return [
            'backend' => [
                'path' => self::BACKEND_PREFIX . '/backend',
                'isFrontend' => false,
            ],
            'frontend' => [
                'path' => '/frontend',
                'isFrontend' => true,
            ],
        ];
    }
}
