<?php

namespace App\Http\Utilities;
/**
 * Class ApiCall
 * @package App\Http\Utilities
 */
class ApiCall
{
    /**
     * @var int
     */
    protected $status;
    /**
     * @var array
     */
    protected $response;
    /**
     * @var
     */
    protected $error;
    /**
     * @var array
     */
    protected $headers;
    /**
     * @var mixed
     */
    private $execTimeStart;
    /**
     * @var mixed
     */
    private $execTimeEnd;
    /**
     * @var bool
     */
    private $cached = false;

    /**
     * ApiCall constructor.
     */
    public function __construct()
    {
        $this->status = 500;
        $this->response = ['data' => array()];
        $this->headers = array();
        $this->execTimeStart = microtime(true);
    }

    /**
     * @param $code
     */
    public function setStatusCode($code)
    {
        $this->status = $code;
    }

    /**
     * @return mixed
     */
    public function makeResponse()
    {
        $this->response['execTime'] = $this->getExecTime();
        $this->response['fromCache'] = $this->cached;
        return response()->json($this->response, $this->status, $this->headers);
    }

    /**
     * @param array $data
     */
    public function addData(array $data)
    {
        foreach ($data as $key => $datum) {

            $this->response['data'][$key] = $datum;
        }
//        $this->response['data']['jwt']= $data;
//        array_push($this->response['data'],$data);
    }

    /**
     * @param $time
     */
    public function addExecutionTime($time)
    {
        array_push($this->response['execTime'], $time);
    }

    /**
     * @param $data
     */
    public function setResponseData($data)
    {
        $this->response['data'] = $data;
    }

    /**
     * @return mixed
     */
    public function getExecTime()
    {
        return microtime(true) - $this->execTimeStart;
    }

    /**
     * @return mixed
     */
    public function getExecTimeStart()
    {
        return $this->execTimeStart;
    }

    /**
     * @param mixed $execTimeStart
     */
    public function setExecTimeStart($execTimeStart)
    {
        $this->execTimeStart = $execTimeStart;
    }

    /**
     * @return mixed
     */
    public function getExecTimeEnd()
    {
        return $this->execTimeEnd;
    }

    /**
     * @param mixed $execTimeEnd
     */
    public function setExecTimeEnd($execTimeEnd)
    {
        $this->execTimeEnd = $execTimeEnd;
    }

    /**
     * @return boolean
     */
    public function isCached()
    {
        return $this->cached;
    }

    /**
     * @param boolean $cached
     */
    public function setCached($cached)
    {
        $this->cached = $cached;
    }

}
