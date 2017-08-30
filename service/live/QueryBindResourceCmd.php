<?php
/**
 * 资源(播片/课件) 关联列表查询
 */
require_once dirname(__FILE__) . '/../../Path.php';

require_once SERVICE_PATH . '/TokenCmd.php';
require_once SERVICE_PATH . '/CmdResp.php';
require_once ROOT_PATH . '/ErrorNo.php';
require_once MODEL_PATH . '/BindFile.php';

class QueryBindResourceCmd extends TokenCmd
{
    private $roomnum;
    private $index;
    private $size;

    public function parseInput()
    {
        if (!isset($this->req['roomnum'])) {
            return new CmdResp(ERR_REQ_DATA, 'Lack of roomnum');
        }
        if (!is_int($this->req['roomnum'])) {
            if (is_string($this->req['roomnum'])) {
                $this->req['roomnum'] = intval($this->req['roomnum']);
            }
            else{
                return new CmdResp(ERR_REQ_DATA, ' Invalid roomnum');
            }
        }
        $this->roomnum = $this->req['roomnum'];

        if (!isset($this->req['index'])) {
            return new CmdResp(ERR_REQ_DATA, 'Lack of page index');
        }
        $index = $this->req['index'];
        if ($index !== (int)$index || $index < 0) {
            return new CmdResp(ERR_REQ_DATA, 'Page index should be a non-negative integer');
        }
        if (!isset($this->req['size'])) {
            return new CmdResp(ERR_REQ_DATA, 'Lack of page size');
        }
        $size = $this->req['size'];
        if ($size !== (int)$size || $size < 0 || $size > 50) {
            return new CmdResp(ERR_REQ_DATA, 'Page size should be a positive integer(not larger than 50)');
        }

        $this->index = $index;
        $this->size = $size;
        return new CmdResp(ERR_SUCCESS, '');
    }

    public function handle()
    {
        //获取已经绑定的资源
        $totalCount=0;
        $recordList = BindFile::getList($this->roomnum,$this->uin,null,$this->index, $this->size,$totalCount);
        if (is_null($recordList)) {
            return new CmdResp(ERR_SERVER, 'Server error: get bind file list fail');
        }
        $data = array(
            'total' => (int)$totalCount,
            'bind_files' => $recordList,
        );
        return new CmdResp(ERR_SUCCESS, '', $data);
    }
}
