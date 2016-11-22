<?php

namespace Oro\Bundle\CustomerBundle\Tests\Selenium;

use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CustomerBundle\Tests\Selenium\Pages\Roles;

class RolesTest extends Selenium2TestCase
{
    /** @var array */
    protected $newRole = ['LABEL' => 'NEW_LABEL_', 'ROLE_NAME' => 'NEW_ROLE_'];

    /** @var array */
    protected $defaultRoles = [
        'header' => [
            'ROLE' => 'ROLE',
            'TYPE' => 'TYPE',
            'ACCOUNT' => 'ACCOUNT',
            '' => 'ACTION'
        ],
        'ROLE_BUYER' => [
            'ROLE_BUYER' => 'ROLE_BUYER',
            'Buyer' => 'Buyer',
            '...' => 'ACTION'
        ],
        'ROLE_ADMINISTRATOR' => [
            'ROLE_ADMINISTRATOR' => 'ROLE_ADMINISTRATOR',
            'Administrator' => 'Administrator',
            '...' => 'ACTION'
        ]
    ];

    public function testRolesGrid()
    {
        /** @var Roles $login */
        $login = $this->login();
        $login
            ->openRoles('Oro\Bundle\CustomerBundle')
            ->assertTitle('All - Account Users - Customers');
    }

    public function testRolesGridDefaultContent()
    {
        /** @var Roles $login */
        $login = $this->login();

        $roles = $login->openRoles('Oro\Bundle\CustomerBundle');
        //get grid content
        $records = $roles->getRows();
        $headers = $roles->getHeaders();

        foreach ($headers as $header) {
            /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element $header */
            $content = $header->text();
            static::assertArrayHasKey($content, $this->defaultRoles['header']);
        }

        $checks = 0;
        foreach ($records as $row) {
            /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element $row */
            $columns = $row->elements($this->using('xpath')->value("td[not(contains(@style, 'display: none;'))]"));
            $id = null;
            foreach ($columns as $column) {
                /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element $column */
                $content = $column->text();
                if (is_null($id)) {
                    $id = trim($content);
                }
                if (array_key_exists($id, $this->defaultRoles)) {
                    static::assertArrayHasKey($content, $this->defaultRoles[$id]);
                }
            }
            $checks++;
        }
        static::assertGreaterThanOrEqual(count($this->defaultRoles)-1, $checks);
    }

    /**
     * @return string
     */
    public function testRolesAddSaveAndClose()
    {
        $randomPrefix = WebTestCase::generateRandomString(5);

        $login = $this->login();
        $roleLabel = $this->newRole['LABEL'] . $randomPrefix;

        /** @var Roles $login */
        $roles = $login->openRoles('Oro\Bundle\CustomerBundle')
            ->assertTitle('All - Customer Users - Customers')
            ->add()
            ->assertTitle('Create Customer User Role - Customer Users - Customers')
            ->setLabel($roleLabel)
            ->save()
            ->assertMessage('Customer User Role has been saved')
            ->close();

        //verify new Role
        $roles->openRoles('Oro\Bundle\CustomerBundle');

        static::assertTrue($roles->entityExists(['name' => $roleLabel]));

        return $randomPrefix;
    }

    /**
     * @depends testRolesAddSaveAndClose
     * @param string $randomPrefix
     */
    public function testRoleDelete($randomPrefix)
    {
        $login = $this->login();
        /** @var Roles $login */
        $roles = $login->openRoles('Oro\Bundle\CustomerBundle');
        $roles->delete(['name' => $this->newRole['LABEL'] . $randomPrefix]);
        static::assertFalse($roles->entityExists(['name' => $this->newRole['LABEL'] . $randomPrefix]));
    }

    /**
     * {@inheritdoc}
     */
    public function login($userName = null, $password = null, $args = [])
    {
        return parent::login($userName, $password, ['url' => '/admin']);
    }
}
