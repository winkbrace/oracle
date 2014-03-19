<?php namespace Oracle\Dump;

use Oracle\Query\Statement;
use Oracle\Support\SimpleView;

class Debug
{
    /** @var \Oracle\Query\Statement */
    protected $statement;

    /**
     * @param Statement $statement
     */
    public function __construct(Statement $statement)
    {
        $this->statement = $statement;
    }

    /**
     * @return string
     */
    public function render()
    {
        $data = array(
            'sql' => $this->statement->getSql(),
            'binds' => $this->statement->toStringBindVariables(),
            'schema' => $this->statement->getSchema(),
        );

        $view = new SimpleView(__DIR__ . '/debug.phtml', $data);

        return $view->render();
    }
}
