<?php namespace Oracle\Export;

/**
 * Class FlatFile
 * @package Oracle\Export
 *
 * This class is responsible for writing the result of a query to a flat file.
 */
class FlatFileWriter
{
    const FILE_APPEND = 'a';
    const FILE_OVERWRITE = 'w';

    /** @var FlatFileStreamer */
    protected $streamer;
    /** @var bool */
    protected $showHeaders = true;
    /** @var string */
    protected $filename;
    /** @var string */
    protected $type;
    /** @var int */
    protected $fileWriteMode = self::FILE_OVERWRITE;
    /** @var int */
    protected $linesPerFile;
    /** @var int */
    protected $fileSequence = 0;
    /** @var resource */
    protected $handle;


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
     * write the query output to the file(s)
     */
    public function write()
    {
        $this->startFile();

        $i = 0;
        while ($line = $this->streamer->next())
        {
            fwrite($this->handle, $line);

            if ($this->atMaxLineSize(++$i))
                $this->goToNextFile();
        }

        $this->endFile();
    }

    protected function startFile()
    {
        $this->handle = fopen($this->filename, $this->fileWriteMode);
        $this->writeHeadersIfRequired();
    }

    protected function endFile()
    {
        if (is_resource($this->handle))
            fclose($this->handle);

        chmod($this->filename, 0775);
    }

    protected function writeHeadersIfRequired()
    {
        if ($this->showHeaders)
            fwrite($this->handle, $this->streamer->getHeadersLine());
    }

    /**
     * @param int $i
     * @return bool
     */
    protected function atMaxLineSize($i)
    {
        if (empty($this->linesPerFile))
            return false;

        return ($i % $this->linesPerFile == 0);
    }

    /**
     * set the name for the next file in the sequence to write to
     */
    protected function goToNextFile()
    {
        $this->endFile();

        // create _01 sequence for first file
        if ($this->fileSequence === 0)
            $this->createFileSequence();

        $info = pathinfo($this->filename);

        $this->filename = $info['dirname'] . '/' . substr($info['filename'], 0, -3) . '_' . sprintf("%02d", ++$this->fileSequence) . '.' . $info['extension'];

        $this->startFile();
    }

    /**
     * create a sequence suffix in the filename of the first file
     */
    protected function createFileSequence()
    {
        $info = pathinfo($this->filename);
        $oldName = $this->filename;
        $this->filename = $info['dirname'] . '/' . $info['filename'] . '_01.' . $info['extension'];
        $this->fileSequence = 1;

        rename($oldName, $this->filename);
    }

    /**
     * @param int $fileWriteMode
     * @throws \InvalidArgumentException
     */
    public function setFileWriteMode($fileWriteMode)
    {
        if (! in_array($fileWriteMode, array(self::FILE_APPEND, self::FILE_OVERWRITE)))
            throw new \InvalidArgumentException('Invalid file write mode specified');

        $this->fileWriteMode = $fileWriteMode;
    }

    /**
     * @param int $linesPerFile
     */
    public function setLinesPerFile($linesPerFile)
    {
        $this->linesPerFile = (int) $linesPerFile;
    }

    /**
     * @param boolean $showHeaders
     */
    public function setShowHeaders($showHeaders)
    {
        $this->showHeaders = (bool) $showHeaders;
    }

    public function __destroy()
    {
        $this->endFile();
    }

}
