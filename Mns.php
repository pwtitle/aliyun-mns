<?php
namespace Aliyun; 

/**
* 为 Aliyun Mns编写的phpSDK。
*
* @author will <developer@panwei.me>
* @todo listQueue方法的特有Request Header
* @see https://docs.aliyun.com/?spm=5176.383338.201.106.Ylehmh#/pub/mns
* @version 20150918
*/
class Mns {
	
	private $webService;
	private $queueName = 'testQueueName';


	/**
	* 构造，
	*
	* 注意，$host可以从MNS管理后台获取，只取url中的host部分即可。
	*
	* @param string $host $accountId.mns.cn-hangzhou-internal.aliyuncs.com
	* @param string $accessKeyId Aliyun提供的AccessKeyId
	* @param string $accessKeySecret Aliyun提供的AccessKeySecret
	*
	* @return Mns MnsObject
	*/
	public function __construct($host, $accessKeyId, $accessKeySecret){
		$this->webService = new webService($host, $accessKeyId, $accessKeySecret);
	}


	public function createQueue($queueName, $delaySeconds = 0, $maximumMessageSize = 65536, $messageRetentionPeriod = 345600, $visibilityTimeout = 30, $PollingWaitSeconds = 0){
		$body  = '<Queue xmlns="http://mns.aliyuncs.com/doc/v1/">';
		$body .= '<DelaySeconds>'. $delaySeconds .'</DelaySeconds>';
		$body .= '<VisibilityTimeout>' . $visibilityTimeout . '</VisibilityTimeout>';
		$body .= '<MaximumMessageSize>' . $maximumMessageSize . '</MaximumMessageSize>';
		$body .= '<MessageRetentionPeriod>'. $messageRetentionPeriod .'</MessageRetentionPeriod>';
		$body .= '</Queue>';

		return $this->webService->put("/queues/" . $queueName, $body);
	}


	public function setQueueAttributes($queueName, $delaySeconds = 0, $maximumMessageSize = 65536, $messageRetentionPeriod = 345600, $visibilityTimeout = 30, $PollingWaitSeconds = 0){
		$body  = '<Queue xmlns="http://mns.aliyuncs.com/doc/v1/">';
		$body .= '<DelaySeconds>'. $delaySeconds .'</DelaySeconds>';
		$body .= '<VisibilityTimeout>' . $visibilityTimeout . '</VisibilityTimeout>';
		$body .= '<MaximumMessageSize>' . $maximumMessageSize . '</MaximumMessageSize>';
		$body .= '<MessageRetentionPeriod>'. $messageRetentionPeriod .'</MessageRetentionPeriod>';
		$body .= '</Queue>';

		return $this->webService->put("/queues/" . $queueName . "?metaoverride=true", $body);
	}


	public function getQueueAttributes($queueName){
		return $this->webService->get("/queues/" . $queueName);
	}


	public function deleteQueue($queueName){
		return $this->webService->delete("/queues/" . $queueName);
	}


	public function listQueue(){
		return $this->webService->get("/queues");
	}


	public function setCurrentQueue($queueName){
		$this->queueName = $queueName;
	}


	public function sendMessage($messageBody){
		$body = "<Message xmlns=\"http://mns.aliyuncs.com/doc/v1/\"><MessageBody>" . $messageBody . "</MessageBody></Message>";
		return $this->webService->post("/queues/". $this->queueName . "/messages", $body);
	}


	public function batchSendMessage($messages){
		$body  = '<Messages xmlns="http://mns.aliyuncs.com/doc/v1/">';
		foreach($messages as $message){
			$body .= '<Message>';
			$body .= '<MessageBody>' . $message['messageBody'] . '</MessageBody>';
			$body .= '<DelaySeconds>' . empty($message['delaySeconds']) ? 0 : $message['delaySeconds']. '</DelaySeconds>';
			$body .= '<Priority>' . empty($message['priority']) ? 8 : $message['priority']. '</Priority>';
			$body .= '</Message>';
		}
		$body .= '</Messages>';

		return $this->webService->post("/queues/". $this->queueName . "/messages", $body);
	}


	public function receiveMessage($waitseconds = 0){
		return $this->webService->get("/queues/" . $this->queueName . "/messages?waitseconds=" . $waitseconds);
	}


	public function batchReceiveMessage($numOfMessages = 10, $waitseconds = 0){
		return $this->webService->get("/queues/" . $this->queueName . "/messages?numOfMessages=" . $numOfMessages . '&waitseconds=' . $waitseconds);
	}


	public function deleteMessage($receiptHandle){
		return $this->webService->delete("/queues/" . $this->queueName. "/messages?ReceiptHandle=" . $receiptHandle);	
	}

	public function batchDeleteMessage($receiptHandles){
		$body = '<ReceiptHandles xmlns="http://mns.aliyuncs.com/doc/v1/">';
		foreach($receiptHandles as $receiptHandle){
			$body .= '<ReceiptHandle>' . $receiptHandle . '</ReceiptHandle>';
		}
		$body .= '</ReceiptHandles>';
		return $this->webService->delete("/queues/" . $this->queueName . "/messages", $body);
	}


	public function peekMessage($peekonly = true){
		return $this->webService->get("/queues/" . $this->queueName . "/messages?peekonly=" . $peekonly);	
	}


	public function batchPeekMessage($peekonly = true, $numOfMessages = 10){
		return $this->webService->get("/queues/" . $this->queueName . "/messages?peekonly=" . $peekonly . "&numOfMessages=" . $numOfMessages);
	}


	public function changeMessageVisibility($receiptHandle, $visibilityTimeout = 30){
		return $this->webService->put("/queues/" . $this->queueName . "/messages?receiptHandle=" . $receiptHandle . '&visibilityTimeout=' . $visibilityTimeout);
	}


}



class webService {

	private $gmtDate = '';
	private $api = '';
	private $method = 'POST';
	private $contentMd5 = '';

	public function __construct($host, $accessKeyId, $accessKeySecret){
		$this->accessKeyId = $accessKeyId;
		$this->accessKeySecret = $accessKeySecret;
		$this->host = $host;
		setlocale(LC_TIME, 'en_US');
	}

	public function __call($method, $arguments){
		$this->method = strtoupper($method);
		$this->api = $arguments[0];

		return $this->call($arguments[1]);
	}


	private function getAuthorization(){
		$str  = $this->method . "\n";
		$str .= $this->contentMd5 . "\n";
		$str .= 'text/xml' . "\n";
		$str .= $this->gmtDate. "\n";
		$str .= 'x-mns-version:2015-06-06' . "\n";
		$str .= $this->api;
		$sign = base64_encode(hash_hmac("sha1", $str, $this->accessKeySecret, true));
		return $sign;		
	}

	private function call($body = ''){
		$this->gmtDate = gmstrftime("%a, %d %b %Y %T %Z", time());
		$this->contentMd5 = base64_encode(md5($body, true));

		$headers = [
			'Authorization: MNS '. $this->accessKeyId . ':' . $this->getAuthorization(),
			'Content-Length: ' . strlen($body),
			'Content-Type: text/xml',
			'Content-MD5: ' . $this->contentMd5,
			'Date: ' . $this->gmtDate,
			'Host: ' . $this->host,
			'x-mns-version: 2015-06-06',
		];
		if(!function_exists('curl_init')) throw new Exception("No CURL extension.");

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://" . $this->host . $this->api);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->method);
		$response = curl_exec($ch);

		if($response === false) throw new Exception(curl_error($ch), curl_errno($ch));

		curl_close($ch);
		return simplexml_load_string($response);
	}


}


