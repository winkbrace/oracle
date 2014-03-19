<?php namespace Oracle;

use Oracle\Dump\Debug;
use Oracle\Dump\Error;
use Oracle\Output\FusionCharter;
use Oracle\Output\Inverter;
use Oracle\Output\Pivoter;
use Oracle\Query\Binder;
use Oracle\Query\Executor;
use Oracle\Query\Fetcher;
use Oracle\Query\Statement;
use Oracle\Result\Result;

/**
 * Class LazyLoader
 *
 * This class is responsible for lazy loading components required by the Query Adapter
 */
class LazyLoader
{
    /** @var Statement */
    protected $statement;
    /** @var Result */
    protected $result;
    /** @var Executor */
    protected $executor;
    /** @var Binder */
    protected $binder;
    /** @var Fetcher */
    protected $fetcher;
    /** @var Pivoter */
    protected $pivoter;
    /** @var Inverter */
    protected $inverter;
    /** @var FusionCharter */
    protected $fusionCharter;
    /** @var Error */
    protected $error;
    /** @var Debug */
    protected $debug;


    /**
     * @param Statement $statement
     */
    public function __construct(Statement $statement)
    {
        $this->statement = $statement;
    }

    /**
     * @return Executor
     */
    public function loadExecutor()
    {
        if (is_null($this->executor))
            $this->executor = new Executor($this->statement);

        return $this->executor;
    }

    /**
     * @return Binder
     */
    public function loadBinder()
    {
        if (is_null($this->binder))
            $this->binder = new Binder($this->statement);

        return $this->binder;
    }

    /**
     * @return Fetcher
     */
    public function loadFetcher()
    {
        if (is_null($this->fetcher))
            $this->fetcher = new Fetcher($this->statement);

        return $this->fetcher;
    }

    /**
     * @return Pivoter
     */
    public function loadPivoter()
    {
        if (is_null($this->pivoter))
        {
            $result = $this->loadResult();
            $this->pivoter = new Pivoter($result);
        }

        return $this->pivoter;
    }

    /**
     * @return Inverter
     */
    public function loadInverter()
    {
        if (is_null($this->inverter))
        {
            $result = $this->loadResult();
            $this->inverter = new Inverter($result);
        }

        return $this->inverter;
    }

    /**
     * @return FusionCharter
     */
    public function loadFusionCharter()
    {
        if (is_null($this->fusionCharter))
        {
            $result = $this->loadResult();
            $this->fusionCharter = new FusionCharter($result);
        }

        return $this->fusionCharter;
    }

    /**
     * @return Result
     */
    public function loadResult()
    {
        if (is_null($this->result))
        {
            $this->loadFetcher();
            $this->result = $this->fetcher->fetchAll();
        }

        return $this->result;
    }

    /**
     * @param string $customMessage
     * @return Error
     */
    public function loadError($customMessage = '')
    {
        if (is_null($this->error))
            $this->error = new Error($this->statement, $customMessage);

        return $this->error;
    }

    /**
     * @return Debug
     */
    public function loadDebug()
    {
        if (is_null($this->debug))
            $this->debug = new Debug($this->statement);

        return $this->debug;
    }
}
