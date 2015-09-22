# aliyun-mns
Aliyun MNS PHP SDK
最后更新 v20150918




	$accountId = "请从阿里云后台获取";
	$accessKeyId = "请从阿里云后台获取";
	$accessKeySecret = "请从阿里云后台获取"；

	require_once 'Mns.php';
	$mns = new Aliyun\Mns("$accountId.mns.cn-hangzhou-internal.aliyuncs.com", $accessKeyId, $accessKeySecret);

	// 创建队列
	$result = $mns->createQueue("test");
	print_r($result);

	// 列出所有队列
	$queues = $mns->listQueue();
	print_r($queues);

	// 设置当前操作的消息队列
	$mns->setCurrentQueue("test");

	// 发消息
	$result = $mns->sendMessage("消息内容");
	print_r($result);

	// 收消息
	$message = $mns->receiveMessage();
	print_r($message);

	// 删除消息
	$result = $mns->deleteMessage($message->ReceiptHandle);
	print_r($result);



