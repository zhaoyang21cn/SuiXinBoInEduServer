## 随心播.教育 Server 接口文档

### 版本说明

版本  | 时间  | 备注
:-----: | :-----: | :-----: 
1.0|2017.8.9|init

### 更新日志

### 功能说明

本代码完整演示了独立账户模式下互动直播业务在线教育场景的功能。可以直接和客户端demo配合使用，迅速体验互动直播的强大功能。

#### 接口列表

* 注册
* 登录
* 下线(退出)

* 创建课堂
* 开课
* 下课
* 心跳上报
* 拉取课程列表
* 上报房间成员变化(成员进出房间)
* 拉取房间成员列表
* 课件/播片源关联上报
* 查询已关联的课件/播片
* 请求cos签名
* 请求vod签名
* 请求vod客户端上传签名
* 录制结束回调接口
* 索引文件生成完成回调接口


#### 需自行实现的功能点
* 各种签名的续期,包括但不限于客户端token, 腾讯IM云userSig,Cos多次签名, vod签名.


### 请求方式

http POST提交数据，请求字段和应答字段以json封装。

### 通用字段说明
注:在例子中,通用字段可能没有列出来.正式请求时请带上来.

Response公共字段说明

字段  | 类型  | 选项 | 说明
:-----: | :-----: | :-----: | :-----: 
errorCode|Integer|必填|错误码
errInfo|String|必填|错误信息
data|Object|可选|返回数据内容
注:如果接口本身没有数据需要返回，则无data字段<br/>

公共参数

字段  | 类型  | 选项 | 说明
:-----: | :-----: | :-----: | :-----: 
timeStamp|Integer|必填|时间戳(和音视频数据包中的时间戳一样:当前时间的毫秒低32位)
appid|Integer|必填|要使用的SdkAppID

错误码

数值  |  说明
:-----: | :-----: 
0|成功
10001|请求有误
10002|请求json错误
10003|请求数据错误
10004|用户已经注册
10005|用户不存在
10006|密码有误
10007|重复登录
10008|重复退出
10009|token过期
10010|直播房间不存在
20001|用户没有av房间ID
20002|用户没有在直播
90000|服务器内部错误

### 注册

向后台申请注册用户账号

* 请求URL  
 
```html
index.php?svc=account&cmd=regist
```
* request字段示例

```json
 { "id":"user000", "pwd": "密码",role:1}
```

字段  | 类型  | 选项 | 说明
:-----: | :-----: | :-----: | :-----: 
id|String|必填|用户id
pwd|String|必填|密码(采用base64加密)
role|int|必填|1 老师(主播) 0 学生(观众)

* response字段示例

```json
 {"errorCode": 0,"errorInfo": ""}
```

### 登录

登录并获取userSig

* 请求URL  
 
```html
index.php?svc=account&cmd=login
```
* request字段示例

```json
 { "id":"user000", "pwd": "密码"}
```

字段  | 类型  | 选项 | 说明
:-----: | :-----: | :-----: | :-----: 
id|String|必填|用户id
pwd|String|必填|密码(采用base64加密)

* response字段示例

```json
 {  "errorCode": 0,
	"errorInfo": "",
	"data":{
		"userSig":"[usersig]",
		"token":"[token]",
		"role":1
	}
 }
```

字段  | 类型  | 选项 | 说明
:-----: | :-----: | :-----: | :-----: 
userSig|String|必填|userSig用于IM登录
token|String|必填|用户唯一标识(后续请求携带)
role|int|必填|参考 注册协议里role的说明

### 下线(退出)

* 通知后台用户离线

* 请求URL  
 
```html
index.php?svc=account&cmd=logout
```
* request字段示例

```json
 { "token":"[token]" }
```

字段  | 类型  | 选项 | 说明
:-----: | :-----: | :-----: | :-----: 
token|String|必填|用户token

* response字段示例

```json
 {  "errorCode": 0,
	"errorInfo": ""
 }

```

### 创建课堂

申请创建直播房间，返回房间id和群组id. 老师才能调用.

* 请求URL  
 
```html
index.php?svc=live&cmd=create
```
* request字段示例

```json
 {  "token":"[token]",
	  "title":"math",
	  "cover":"http://url.com/a.jpeg"
 }
```

字段  | 类型  | 选项 | 说明
:-----: | :-----: | :-----: | :-----: 
token|String|必填|用户token
title|String|必填|课程名字
cover|String|可选|课程封面图片

* response字段示例

```json
 {  "errorCode": 0,
	"errorInfo": "",
	"data":{
   		"roomnum": 123,
   		"groupid": "123"
	}
 }

```

字段  | 类型  | 选项 | 说明
:-----: | :-----: | :-----: | :-----: 
roomnum|Integer|必填|房间id(服务器分配的唯一房间id)
groupid|String|必填|(重要)IM群组id.客户端用此id去创建群,必须保证创建的群的groupid就是这个值. 否则,需要新加信令上报实际的groupid


### 开课
* 课程正式开讲.  创建课程和开课可能发生在不同的登录.老师可以先预先发布一个课程. 
  老师才能调用.

* 请求URL  
 
```html
index.php?svc=live&cmd=startcourse
```
* request字段示例

```json
 {  "token":"[token]",
	"roomnum":18
 }
```

字段  | 类型  | 选项 | 说明
:-----: | :-----: | :-----: | :-----: 
token|String|必填|用户token
roomnum|Integer|必填|房间id

* response字段示例

```json
 {  "errorCode": 0,
	"errorInfo": ""
 }

```

### 下课

* 退出房间后上报信息. 下课会销毁房间.老师才能调用

* 请求URL  
 
```html
index.php?svc=live&cmd=exitroom
```
* request字段示例

```json
 {  "token":"[token]",
	"roomnum":18
 }
```

字段  | 类型  | 选项 | 说明
:-----: | :-----: | :-----: | :-----: 
token|String|必填|用户token
roomnum|Integer|必填|房间id

* response字段示例

```json
 {  "errorCode": 0,
	"errorInfo": ""
 }

```

### 心跳上报

* 用户在房间内定时进行心跳(3s)上报.学生和老师都需要发心跳.

* 请求URL  
 
```html
index.php?svc=live&cmd=heartbeat
```
* request字段示例

```json
 {  "token":"[token]",
	"roomnum":123
 }
```

字段  | 类型  | 选项 | 说明
:-----: | :-----: | :-----: | :-----: 
token|String|必填|用户token
roomnum|Integer|必填|房间id

* response字段示例

```json
 {  "errorCode": 0,
	"errorInfo": ""
 }

```

### 拉取课程列表

* 可以带条件,拉取课程列表.多个场景下调用.A.老师拉取自己创建的课程.B.学生拉取所有直播的课程.C.学生拉取可以回放的课程列表

* 请求URL  
 
```html
index.php?svc=live&cmd=roomlist
```
* request字段示例

```json
 {  "token":"[token]",
	"index":0,
	"size":10,
	"from_time":1412345678,
	"to_time":1412346678
	"host_uid":"teacher",
	"state":0
 }
```

字段  | 类型  | 选项 | 说明
:-----: | :-----: | :-----: | :-----: 
token|String|必填|用户token
index|Integer|必填|起始房间位置(从0开始)
size|Integer|必填|列表长度
roomnum|Integer|选填|如果只需要查找某个具体的课程,请指定这个字段
from_time|Integer|选填|搜索开始时间戳(1970年1月1日以来的秒数)
to_time|Integer|选填|搜索结束时间戳(1970年1月1日以来的秒数)
host_uid|String|选填|要搜索的老师id. 没有这个字段表示搜索所有老师的
state|Interger|选填|要拉取的课程的状态. 没有这个字段表示全部状态

课程state取值

state取值 | 描述
:-----: | :-----: 
0|已创建未上课
1|正在上课中
2|已下课但不能回放
3|可以回放


* response字段示例

```json
 {  "errorCode": 0,
	"errorInfo": "",
	"data":{
	"total":100,
 	"rooms":[{
		     "host_uid":"[uid]",
          	 "title": "标题",
             "state":3,
             "roomnum":18,
             "groupid":"18",
             "cover":"http://cover.png",
             "memsize":23,
             "playback_idx_url":"http://xxxxx",
             "begin_time":145668974,
             "begin_imseq":145,
             "end_time":145668974,
             "end_imseq":1456
        },
        {
		    "host_uid":"[uid]",
            "title": "标题",
            "state":0,
            "roomnum":19,
            "groupid":"19",
            "cover":"http://cover.png",
            "memsize":23,
            "playback_idx_url":"",
            "begin_time":145668974,
            "begin_imseq":145,
            "end_time":145668974, 
            "end_imseq":1456  
        }
    ]}
 }
```

字段  | 类型  | 选项 | 说明
:-----: | :-----: | :-----: | :-----: 
total|Integer|必填|房间总数
rooms|Array|必填|房间信息数组

房间信息

字段  | 类型  | 选项 | 说明
:-----: | :-----: | :-----: | :-----:
host_uid|String|必填|老师id 
title|String| 选填|标题
state|Interger|必填|课程状态
roomnum|Integer|必填|房间id
groupid|String|必填|群组id
cover|String| 选填|封面地址
memsize|Integer|必填|课程参与人数
playback_idx_url|String| 选填|回放索引文件地址
begin_time|Integer|必填|课程开始时间
begin_imseq|Integer|必填|课程开始时对应的im消息的seqno
end_time|Integer|必填|课程结束时间
end_imseq|Integer|必填|课程结束时时对应的im消息的seqno

### 上报房间成员变化(成员进出房间)

* 在腾讯视频云加入房间后，上报加入房间信息

* 请求URL  
 
```html
index.php?svc=live&cmd=reportmemid
```
* request字段示例

```json
 {  "token":"[token]",
	"roomnum":18,
	"operate":0
 }
```

字段  | 类型  | 选项 | 说明
:-----: | :-----: | :-----: | :-----: 
token|String|必填|用户token
roomnum|int|必填|房间号
operate|int|必填| 进入房间 0 离开房间 1


* response字段示例

```json
 {  "errorCode": 0,
	"errorInfo": ""
 }

```

### 拉取房间成员列表

* 获取房间成员列表

* 请求URL  
 
```html
index.php?svc=live&cmd=roomidlist
```
* request字段示例

```json
 {  "token":"[token]",
	"roomnum":18,
	"index":0,
	"size":10
 }
```

字段  | 类型  | 选项 | 说明
:-----: | :-----: | :-----: | :-----: 
token|String|必填|用户token
roomnum|Integer|必填|房间id
index|Integer|必填|起始位置(从0开始)
size|Integer|必填|列表长度

* response字段示例

```json
 {  "errorCode": 0,
	"errorInfo": "",
	"data":{
   		"total":100,
   		"idlist":[{
           "id":"willduo",
           "role":1
       	}]
	}
 }

```

字段  | 类型  | 选项 | 说明
:-----: | :-----: | :-----: | :-----: 
total|Integer|必填|id总数
idlist|Array|必填|房间id信息数组

id信息

字段  | 类型  | 选项 | 说明
:-----: | :-----: | :-----: | :-----: 
id|String|必填|id
role|int|必填|1 老师(主播) 0 学生(观众)




### 课件/播片源关联上报

* 在上传课件到cos后. 关联/取消关联课件/播片源到本课程

* 请求URL  
 
```html
index.php?svc=live&cmd=reportbind
```
* request字段示例

```json
 {  "token":"[token]",
	  "roomnum":18,
	  "operate":0,
	  "bind_file":{
                    "type":0,
                    "file_name":"file_name",
		    "url":"[url]"
     }
 }
```

字段  | 类型  | 选项 | 说明
:-----: | :-----: | :-----: | :-----: 
token|String|必填|用户token
roomnum|int|必填|房间号
operate|int|必填| 关联 0 取消关联 1
bind_file|Object|必填|课件信息

bind_file信息

字段  | 类型  | 选项 | 说明
:-----: | :-----: | :-----: | :-----: 
type|int|必填|资源类型,0:课件,1:播片
file_name|String|必填|文件名
url|String|必填|资源对应的url

* response字段示例

```json
 {  "errorCode": 0,
	"errorInfo": ""
 }

```

### 查询已关联的课件/播片
* 查询课堂已关联的资源列表

* 请求URL  
 
```html
index.php?svc=live&cmd=querybind
```
* request字段示例

```json
 {  "token":"[token]",
    "roomnum":18
    "index":0
    "size":50
 }
```

字段  | 类型  | 选项 | 说明
:-----: | :-----: | :-----: | :-----: 
token|String|必填|用户token
roomnum|int|必填|房间号
index|Integer|必填|起始位置(从0开始)
size|Integer|必填|列表长度

* response字段示例

```json
 {  "errorCode": 0,
    "errorInfo": ""
    "data":{
        "total":2,
        "bind_files":[{
            "type":0,
            "file_name":"file_name",
            "url":"[url]"
         },
         {
            "type":0,
            "file_name":"file_name",
            "url":"[url]"
        }]
    }
 }

```
字段  | 类型  | 选项 | 说明
:-----: | :-----: | :-----: | :-----: 
total|int|必填|记录数
bind_files|Array|必填|bind_file数组

bind_file信息

字段  | 类型  | 选项 | 说明
:-----: | :-----: | :-----: | :-----: 
type|int|必填|资源类型,0:课件,1:播片
file_name|String|必填|文件名
url|String|必填|资源对应的url

### 请求cos签名

* 参考:https://www.qcloud.com/document/product/436/6054
* 参考:https://www.qcloud.com/document/product/436/6274
* 请求URL

```php
index.php?svc=cos&cmd=get_sign
```

* request字段示例

```json
 {
   "token":"[token]",
   "type":0,
   "bucket": "[bucket]",
   "file_path": "/test"
 }
```

字段  | 类型  | 选项 | 说明
:-----: | :-----: | :-----: | :-----: 
token|String|必填|用户token
type|Integer|必填|签名类型. 0:多次,1:单次
bucket|String|可选|指定bucket.如果不指定按照默认bucket
file_path|String|可选|文件路径,斜杠开头,为文件在此 bucket下的全路径.当单次签名时,此字段为必填字段.

* response字段示例

```json
 {  "errorCode": 0,
    "errorInfo": "",
    "data":{
      "sign": "[sig]",
      "bucket": "[bucket]",
      "region": "sh",
      "preview_tag": "preview",
    }
 }

```
字段  | 类型  | 选项 | 说明
:-----: | :-----: | :-----: | :-----: 
sign|String|必填|生成的签名
bucket|String|必填|当前使用的bucket
region|String|必填|bucket区域信息,参见官网说明.对于请求里带bucket的,此字段为空
preview_tag|String|必填|文档预览域名[bucket]-[appid].[preview_tag].myqcloud.com中使用

### 请求vod签名

* 参考:https://www.qcloud.com/document/api/377/4214
* 请求URL

```php
index.php?svc=vod&cmd=get_sign
```

* request字段示例

```json
 {
    "token":"[token]",
    "method":"GET",
    "host":"cvm.api.qcloud.com", 
    "path":"/v2/index.php",
    "params": {
           "Action" : "DescribeInstances",
           "Nonce" : 11886,
           "Region" : "gz",
           "SecretId" : "",
           "SignatureMethod" : "HmacSHA256",
           "Timestamp" : 1465185768,
           "instanceIds.0" : "ins-09dx96dg",
           "limit" : 20,
           "offset" : 0,
    }
 }
```

字段  | 类型  | 选项 | 说明
:-----: | :-----: | :-----: | :-----: 
token|String|必填|用户token
method|String|必填|POST 和 GET 方式, 这里使用 GET 请求, 注意方法为全大写
host|String|必填|请求主机.实际的请求域名根据接口所属模块的不同而不同, 详见各接口说明
path|String|必填|云API的请求路径目前固定为/v2/index.php.详见各接口说明
params|Object|必填|调腾讯云vod接口的请求参数列表.详见各接口说明.因为还不知道SecretId所以SecretId为空.SignatureMethod取值HmacSHA1和HmacSHA256

* response字段示例

```json
 {  "errorCode": 0,
    "errorInfo": "",
    "data":{
      "sign": "[sig]",
      "SecretId" : "[SecretId]",
      "SignatureMethod" : "HmacSHA256",
    }
 }

```
字段  | 类型  | 选项 | 说明
:-----: | :-----: | :-----: | :-----: 
sign|String|必填|生成的签名.注意,原始签名,没有进行urlencode
SecretId|String|必填|调用方需要用SecretId来补充腾讯云的请求中的参数
SignatureMethod|String|必填|签名加密方式,HmacSHA256和HmacSHA1.客户端可以用来校对.

### Vod服务端Api命令通道

* 参考具体Api的官方文档.
* 案例中的拉取点播视频文件(DescribeVodInfo)的官方文档:https://www.qcloud.com/document/product/266/7823
* 请求URL

```php
index.php?svc=vod&cmd=cmd_proxy
```

* request字段示例

```json
 {
    "token":"[token]",
    "method":"GET",
    "host":"vod.api.qcloud.com", 
    "path":"/v2/index.php",
    "params": {
           "Action" : "DescribeVodInfo",
           "Nonce" : 11886,
           "Region" : "gz",
           "SecretId" : "",
           "SignatureMethod" : "HmacSHA256",
           "Timestamp" : 1465185768,
           "instanceIds.0" : "ins-09dx96dg",
           "pageNo" : 0,
           "pageSize" : 20,
    }
 }
```

字段  | 类型  | 选项 | 说明
:-----: | :-----: | :-----: | :-----: 
token|String|必填|用户token
method|String|必填|POST 和 GET 方式, 这里使用 GET 请求, 注意方法为全大写
host|String|必填|请求主机.实际的请求域名根据接口所属模块的不同而不同, 详见各接口说明
path|String|必填|云API的请求路径目前固定为/v2/index.php.详见各接口说明
params|Object|必填|调腾讯云vod接口的请求参数列表.详见各接口说明.因为还不知道SecretId所以SecretId为空.SignatureMethod取值HmacSHA1和HmacSHA256

* response字段示例

```json
 {  "errorCode": 0,
    "errorInfo": "",
    "data":{
      "code": 0,
      "message" : "[SecretId]",
      "totalCount" : 0
    }
 }

```
字段  | 类型  | 选项 | 说明
:-----: | :-----: | :-----: | :-----: 
data|Object|必填|透传腾讯云返回值, 参照各接口的官方文档.

### 请求vod客户端上传签名

* 参考:https://www.qcloud.com/document/product/266/9221
* 请求URL

```php
index.php?svc=vod&cmd=get_client_sign
```

* request字段示例

```json
 {
    "token":"[token]"
 }
```

字段  | 类型  | 选项 | 说明
:-----: | :-----: | :-----: | :-----: 
token|String|必填|用户token

* response字段示例

```json
 {  "errorCode": 0,
    "errorInfo": "",
    "data":{
      "sign": "[sig]",
      "SecretId" : "[SecretId]",
    }
 }

```
字段  | 类型  | 选项 | 说明
:-----: | :-----: | :-----: | :-----: 
sign|String|必填|生成的签名.注意,原始签名,没有进行urlencode
SecretId|String|必填|调用方需要用SecretId来补充腾讯云的请求中的参数

### 录制结束回调接口

* 接收旁路直播和录制相关服务器回调的通知

* 请求URL

```php
index.php?svc=live&cmd=recendcall
```
* 这是腾讯视频云后台调用业务后台推送通知的接口，具体处理方式请参考文档和代码

### 索引文件生成完成回调接口

* 接收索引文件生成服务器回调的通知

* 请求URL

```php
index.php?svc=live&cmd=idxfileendcall
```

 




