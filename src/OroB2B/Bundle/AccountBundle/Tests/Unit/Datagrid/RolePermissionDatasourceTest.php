<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;

use OroB2B\Bundle\AccountBundle\Datagrid\RolePermissionDatasource;

class RolePermissionDatasourceTest extends RolePermissionDatasourceTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getDatasource()
    {
        return new RolePermissionDatasource(
            $this->translator,
            $this->permissionManager,
            $this->aclRoleHandler,
            $this->categoryProvider,
            $this->configEntityManager,
            $this->roleTranslationPrefixResolver
        );
    }
    
    /**
     * {@inheritdoc}
     */
    protected function assertResults(array $results, $identity)
    {
        $this->assertCount(2, $results);

        /** @var ResultRecord $record1 */
        $record1 = array_shift($results);

        /** @var ResultRecord $record2 */
        $record2 = array_shift($results);

        $this->assertInstanceOf(ResultRecord::class, $record1);
        $this->assertEquals($identity, $record1->getValue('identity'));
        $this->assertNotEmpty($record1->getValue('permissions'));

        $this->assertInstanceOf(ResultRecord::class, $record2);
        $this->assertEquals($identity . 'User', $record2->getValue('identity'));
        $this->assertEmpty($record2->getValue('permissions'));
    }
}
