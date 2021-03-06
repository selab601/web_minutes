<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\MinutesTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\MinutesTable Test Case
 */
class MinutesTableTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \App\Model\Table\MinutesTable
     */
    public $Minutes;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.minutes',
        'app.projects',
        'app.users',
        'app.projects_users',
        'app.roles',
        'app.participations',
        'app.responsibilities',
        'app.items',
        'app.item_meta_categories',
        'app.item_categories'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::exists('Minutes') ? [] : ['className' => 'App\Model\Table\MinutesTable'];
        $this->Minutes = TableRegistry::get('Minutes', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Minutes);

        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
