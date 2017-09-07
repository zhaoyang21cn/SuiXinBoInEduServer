<?php
require_once dirname(__FILE__) . '/TimRestApi.php';
function createMyRestAPI(){
    return new MyTimRestAPI;
}
class MyTimRestAPI extends TimRestApi
{
	public function set_im_yun_url($im_yun_url)
	{
		$this->im_yun_url = $im_yun_url;
		return true;
	}
}

