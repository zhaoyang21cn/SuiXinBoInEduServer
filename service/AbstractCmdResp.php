<?php
/**
 * 
 */
abstract class AbstractCmdResp
{
    protected $errorCode;
    protected $errorInfo;
    protected $data;

    public function __construct($errorCode, $errorInfo, $data = null)
    {
        $this->errorCode = $errorCode;
        $this->errorInfo = $errorInfo;
        $this->data = $data;
    }

    public function isSuccess()
    {
        return $this->errorCode === ERR_SUCCESS;
    }

    /**
     * @return int
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @return string
     */
    public function getErrorInfo()
    {
        return $this->errorInfo;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return array
     */
    abstract public function toArray();
}
