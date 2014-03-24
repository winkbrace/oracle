<?php namespace Oracle\Query;

/**
 * Class FakeExecutor
 *
 * This class is a test replacement for the normal Executor.
 * It will only check if the sql syntax is valid instead of executing the query
 */
class FakeExecutor extends Executor
{
    public function execute($commit = self::COMMIT)
    {
        if (! $this->isExecuted)
            $this->isExecuted = $this->statement->validateSqlSyntax();

        return $this->isExecuted;
    }

    public function insert($sequence = null, $commit = self::COMMIT)
    {
        throw new \Exception('cannot use insert on FakeExecutor');
    }

    public function executeMultiple($binds, $sizes, $data, $commit = self::COMMIT)
    {
        throw new \Exception('cannot use executeMultiple on FakeExecutor');
    }
}
