<?php namespace Oracle\Support;

class SimpleView
{
    protected $filename;

    /**
     * Construct a View
     * @param string $filename
     * @param array $variables
     */
    function __construct($filename, $variables = null)
    {
        $this->setFilename($filename);
        $this->add($variables);
    }

    /**
     * add a variable to the view
     * @param array $variables (name => value)
     * @throws \InvalidArgumentException
     */
    public function add(array $variables)
    {
        if (empty($variables))
            return;

        foreach ($variables as $name => $value)
            $this->{$name} = $value;
    }

    /**
     * render the view
     * This is a very simple rendering engine. It will crash when a variable is not passed to the view.
     * @return string
     */
    public function render()
    {
        $template = file_get_contents($this->filename);
        $phpcontents = str_replace(array('{', '}'), array('<?php echo $this->', '; ?>'), $template);
        ob_start();
        eval("?>$phpcontents");
        return ob_get_clean();
    }

    /**
     * @param string $filename
     * @throws \InvalidArgumentException
     */
    public function setFilename($filename)
    {
        if (! file_exists($filename))
            throw new \InvalidArgumentException('Invalid view filename: '.$filename);

        $this->filename = $filename;
    }

}
