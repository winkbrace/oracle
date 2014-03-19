<?php namespace Oracle\Export;

use Oracle\Query\Fetcher;

class FlatFileStreamer extends DataStreamer
{
    /** @var string */
    protected $type;
    /** @var string */
    protected $filename;
    /** @var string */
    protected $separator = null;
    /** @var array */
    protected $separatorsPerType = array();
    /** @var string */
    protected $lineEnding = "\r\n";

    /**
     * @param Fetcher $fetcher
     * @param string $filename
     */
    public function __construct(Fetcher $fetcher, $filename)
    {
        $this->fetcher = $fetcher;
        $this->setFilename($filename);

        $this->separatorsPerType = array(
            'txt' => "\t",
            'csv' => ',',
            'psv' => '|',
            'tsv' => '~',
        );
    }

    /**
     * @return string|false
     */
    public function next()
    {
        $row = $this->fetcher->fetch();
        if ($row === false)
            return false;

        return implode($this->getSeparator(), $row->toArray()) . $this->lineEnding;
    }

    /**
     * @return string
     */
    public function getSeparator()
    {
        if (is_null($this->separator))
            $this->setSeparatorByType();

        return $this->separator;
    }

    /**
     * @return string
     */
    public function getType()
    {
        if (empty($this->type))
            $this->setTypeByFileExtension();

        return $this->type;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * set separator based on the file type
     */
    protected function setSeparatorByType()
    {
        $type = $this->getType();
        $this->setSeparator($this->separatorsPerType[$type]);
    }

    /**
     * @param $filename
     */
    protected function setFilename($filename)
    {
        $this->filename = $filename;
    }

    /**
     * @param string $separator
     */
    public function setSeparator($separator)
    {
        $this->separator = $separator;
    }

    /**
     * @param string $type
     * @throws \InvalidArgumentException
     */
    public function setType($type)
    {
        $type = strtolower($type);
        if (! in_array($type, array("txt", "psv", "csv", "tsv")))
            throw new \InvalidArgumentException('Unknown file extension requested.');

        $this->type = $type;
    }

    /**
     * @param string $lineEnding
     */
    public function setLineEnding($lineEnding)
    {
        $this->lineEnding = $lineEnding;
    }

    /**
     *
     */
    protected function setTypeByFileExtension()
    {
        $ext = $this->getFileExtension();
        if (! empty($ext))
            $this->setType($ext);
    }

    /**
     * @return string
     */
    protected function getFileExtension()
    {
        $info = pathinfo($this->filename);
        return $info['extension'];
    }

    /**
     * @return string
     */
    public function getHeadersLine()
    {
        return implode($this->getSeparator(), $this->fetcher->getColumnNames()) . $this->lineEnding;
    }
}
