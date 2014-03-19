<?php namespace Oracle\Dump;

use Oracle\Query\Statement;
use Oracle\Support\SimpleView;

class Error
{
    /** @var \Oracle\Query\Statement */
    protected $statement;
    /** @var string */
    protected $customMessage;
    /** @var array */
    protected $error;

    /**
     * @param \Oracle\Query\Statement $statement
     * @param string $customMessage
     */
    public function __construct(Statement $statement, $customMessage = null)
    {
        $this->statement = $statement;
        $this->customMessage = $customMessage;
        $this->error = oci_error($statement->getResource());
    }

    public function render()
    {
        // if error_reporting = 0 (hide all errors in production) don't show the error
        if (error_reporting() === 0)
            return null;

        $data = array(
            'id' => 'sql' . mt_rand(0, 9999),  // create probably unique id
            'error_message' => $this->getErrorMessage(),
            'known_error_message' => $this->knownErrorMessage(),
            'sql' => $this->statement->getSql(),
            'binds' => $this->statement->toStringBindVariables()
        );

        $view = $this->createErrorView($data);

        return $view->render();
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        $message = $this->customMessage;

        // hide query error messages for normal users
        if (! $this->userIsAdmin())
            return $message;

        if (strlen($message) > 0)
            $message .= '<br/>';

        $message .= $this->error['message'];

        if ($this->userIsAdmin())
            $message .= $this->knownErrorMessage();

        return $message;
    }

    /**
     * @return bool
     */
    protected function userIsAdmin()
    {
        return defined('ACCOUNT_LEVEL') && ACCOUNT_LEVEL == 'ADMIN';
    }

    /**
     * Add helpful text messages for known errors
     * @return string
     */
    protected function knownErrorMessage()
    {
        if (empty($this->error['code']))
            return null;

        switch ($this->error['code'])
        {
            case 1843: // ORA-01843: not a valid month
                return "\n".'Did you try to insert a string in a date field?';
            case 1036: // ORA-01036: illegal variable name/number
                return "\n".'Did one of the bind variables not receive a value?';
            default:
                return null;
        }
    }

    /**
     * @param $data
     * @return \Oracle\Support\SimpleView
     */
    protected function createErrorView($data)
    {
        $page = __DIR__ . ($this->userIsAdmin() ? '/admin_error.phtml' : '/error.phtml');
        $view = new SimpleView($page, $data);

        return $view;
    }
}
