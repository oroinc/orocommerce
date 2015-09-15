<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Owner\Metadata;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;

use OroB2B\Bundle\AccountBundle\Owner\Metadata\FrontendOwnershipMetadata;

class FrontendOwnershipMetadataTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array $ownerType
     * @param int $expectedOwnerType
     * @param array $exceptionDefinition
     *
     * @dataProvider frontendOwnerTypeException
     */
    public function testSetFrontendOwner(array $ownerType, $expectedOwnerType, array $exceptionDefinition = [])
    {
        if ($exceptionDefinition) {
            list ($exception, $message) = $exceptionDefinition;
            $this->setExpectedException($exception, $message);
        }

        list ($frontendOwnerType, $frontendOwnerFieldName, $frontendOwnerColumnName) = $ownerType;
        $metadata = new FrontendOwnershipMetadata(
            $frontendOwnerType,
            $frontendOwnerFieldName,
            $frontendOwnerColumnName
        );

        $this->assertEquals($expectedOwnerType, $metadata->getOwnerType());
        $this->assertEquals($frontendOwnerFieldName, $metadata->getOwnerFieldName());
        $this->assertEquals($frontendOwnerColumnName, $metadata->getOwnerColumnName());
    }

    /**
     * @return array
     */
    public function frontendOwnerTypeException()
    {
        return [
            [
                ['FRONTEND_USER', 'account_user', 'account_user_id'],
                FrontendOwnershipMetadata::OWNER_TYPE_FRONTEND_USER,
            ],
            [
                ['FRONTEND_ACCOUNT', 'FRONTEND_ACCOUNT', 'account_id'],
                FrontendOwnershipMetadata::OWNER_TYPE_FRONTEND_ACCOUNT,
            ],
            [
                ['UNKNOWN', 'FRONTEND_ACCOUNT', 'account_id'],
                FrontendOwnershipMetadata::OWNER_TYPE_FRONTEND_ACCOUNT,
                [
                    '\InvalidArgumentException',
                    'Unknown owner type: UNKNOWN.',
                ],
            ],
            [
                ['UNKNOWN', 'FRONTEND_ACCOUNT', 'account_id'],
                FrontendOwnershipMetadata::OWNER_TYPE_FRONTEND_ACCOUNT,
                [
                    '\InvalidArgumentException',
                    'Unknown owner type: UNKNOWN.',
                ],
            ],
            [
                ['', '', ''],
                FrontendOwnershipMetadata::OWNER_TYPE_NONE,
            ],
            [
                ['FRONTEND_ACCOUNT', '', 'account_id'],
                FrontendOwnershipMetadata::OWNER_TYPE_FRONTEND_ACCOUNT,
                [
                    '\InvalidArgumentException',
                    'The owner field name must not be empty.',
                ],
            ],
            [
                ['FRONTEND_ACCOUNT', 'FRONTEND_ACCOUNT', ''],
                FrontendOwnershipMetadata::OWNER_TYPE_FRONTEND_ACCOUNT,
                [
                    '\InvalidArgumentException',
                    'The owner column name must not be empty.',
                ],
            ],
        ];
    }

    public function testIsBasicLevelOwned()
    {
        $metadata = new FrontendOwnershipMetadata();
        $this->assertFalse($metadata->isBasicLevelOwned());

        $metadata = new FrontendOwnershipMetadata('FRONTEND_USER', 'account_user', 'account_user_id');
        $this->assertTrue($metadata->isBasicLevelOwned());

        $metadata = new FrontendOwnershipMetadata('FRONTEND_ACCOUNT', 'FRONTEND_ACCOUNT', 'account_id');
        $this->assertFalse($metadata->isBasicLevelOwned());
    }

    public function testIsLocalLevelOwned()
    {
        $metadata = new FrontendOwnershipMetadata();
        $this->assertFalse($metadata->isLocalLevelOwned());
        $this->assertFalse($metadata->isLocalLevelOwned(true));

        $metadata = new FrontendOwnershipMetadata('FRONTEND_ACCOUNT', 'FRONTEND_ACCOUNT', 'account_id');
        $this->assertTrue($metadata->isLocalLevelOwned());
        $this->assertTrue($metadata->isLocalLevelOwned(true));

        $metadata = new FrontendOwnershipMetadata('FRONTEND_USER', 'account_user', 'account_user_id');
        $this->assertFalse($metadata->isLocalLevelOwned());
        $this->assertFalse($metadata->isLocalLevelOwned(true));
    }

    public function testSerialization()
    {
        $metadata = new FrontendOwnershipMetadata('FRONTEND_USER', 'account_user', 'account_user_id');
        $data = serialize($metadata);

        $metadata = new FrontendOwnershipMetadata();
        $this->assertFalse($metadata->isBasicLevelOwned());
        $this->assertFalse($metadata->isLocalLevelOwned());
        $this->assertEquals('', $metadata->getOwnerFieldName());
        $this->assertEquals('', $metadata->getOwnerColumnName());

        $metadata = unserialize($data);
        $this->assertTrue($metadata->isBasicLevelOwned());
        $this->assertFalse($metadata->isLocalLevelOwned());
        $this->assertEquals('account_user', $metadata->getOwnerFieldName());
        $this->assertEquals('account_user_id', $metadata->getOwnerColumnName());
    }

    public function testIsGlobalLevelOwned()
    {
        $metadata = new FrontendOwnershipMetadata();
        $this->assertFalse($metadata->isGlobalLevelOwned());
    }

    /**
     * @param array $arguments
     * @param array $levels
     * @dataProvider getAccessLevelNamesDataProvider
     */
    public function testGetAccessLevelNames(array $arguments, array $levels)
    {
        $reflection = new \ReflectionClass('OroB2B\Bundle\AccountBundle\Owner\Metadata\FrontendOwnershipMetadata');
        /** @var FrontendOwnershipMetadata $metadata */
        $metadata = $reflection->newInstanceArgs($arguments);
        $this->assertEquals($levels, $metadata->getAccessLevelNames());
    }

    /**
     * @return array
     */
    public function getAccessLevelNamesDataProvider()
    {
        return [
            'no owner' => [
                'arguments' => [],
                'levels' => [
                    0 => AccessLevel::NONE_LEVEL_NAME,
                    5 => AccessLevel::getAccessLevelName(5),
                ],
            ],
            'basic level owned' => [
                'arguments' => ['FRONTEND_USER', 'owner', 'owner_id'],
                'levels' => [
                    0 => AccessLevel::NONE_LEVEL_NAME,
                    1 => AccessLevel::getAccessLevelName(1),
                    2 => AccessLevel::getAccessLevelName(2),
                ],
            ],
            'local level owned' => [
                'arguments' => ['FRONTEND_ACCOUNT', 'owner', 'owner_id'],
                'levels' => [
                    0 => AccessLevel::NONE_LEVEL_NAME,
                    2 => AccessLevel::getAccessLevelName(2),
                ],
            ],
        ];
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Owner type 1 is not supported
     */
    public function testGetAccessLevelNamesInvalidOwner()
    {
        $metadata = new FrontendOwnershipMetadata('ORGANIZATION', 'owner', 'owner_id');
        $metadata->getAccessLevelNames();
    }
}
