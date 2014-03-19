<?php namespace Oracle\Export;

/**
 * Class FlatFileSender
 *
 * This class is responsible for sending the result array as flat file via proper headers
 */
class FlatFileSender
{
    /** @var FlatFileStreamer */
    protected $streamer;
    /** @var bool */
    protected $showHeaders = true;
    /** @var string */
    protected $filename;
    /** @var string */
    protected $type;


    /**
     * @param FlatFileStreamer $streamer
     */
    public function __construct(FlatFileStreamer $streamer)
    {
        $this->streamer = $streamer;
        $this->filename = $streamer->getFilename();
        $this->type = $streamer->getType();
    }

    /**
     * @param boolean $showHeaders
     */
    public function setShowHeaders($showHeaders)
    {
        $this->showHeaders = (bool) $showHeaders;
    }

    /**
     * send the flat file output to the client
     */
    public function send()
    {
        $this->sendHeaders();
        $this->echoContents();
        exit;
    }

    /**
     *
     */
    protected function sendHeaders()
    {
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header("Content-Disposition: attachment; filename=$this->filename.$this->type");

        if ($this->type == 'csv')
            header("Content-Type: text/comma-separated-values");
        else
            header("Content-Type: text/plain");
    }

    /**
     * echo the contents as a stream
     */
    protected function echoContents()
    {
        if ($this->showHeaders)
            echo $this->streamer->getHeadersLine();

        while ($line = $this->streamer->next())
            echo $line;
    }

}
