<?php
App::uses('CakeTestSuite', 'TestSuite');
class AllAuditableTestsTest extends CakeTestSuite
{
    public static function suite()
    {
        $suite = new CakeTestSuite('All Auditable Tests');

        $suite->addTestDirectory(__DIR__ . '/Model');
        $suite->addTestDirectory(__DIR__ . '/Model/Behavior');

        return $suite;
    }
}
