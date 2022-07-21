<?php

namespace common\components;

use paragraph1\phpFCM\Client;
use paragraph1\phpFCM\Message;
use paragraph1\phpFCM\Recipient\Device;
use paragraph1\phpFCM\Notification;
use paragraph1\phpFCM\Recipient\Topic;

class MobileNotifyer {

    public static function sendNotifyre($title, $message, $addresat, $aditionalData = null){
        $apiKey = 'AAAAjW1lAu4:APA91bE8tX5X1U-F7LtzYUWVAHXer-sMpjNiOeOD8iQF8DRMgJNeTdliuLBcCkcwOr7JDIsl6q-AfQ8gDNR782lw6yhsWfFn0SD0o4LKITG5b3bkh5x32zVqRfXIDH33ELQt_v1Co3QH';
        $client = new Client();
        $client->setApiKey($apiKey);
        $client->injectHttpClient(new \GuzzleHttp\Client());

        $note = new Notification($title, $message);
        $note->setIcon('notification_icon_resource_name')
            ->setColor('#ffffff')
            ->setBadge(1);

        $message = new Message();
        $message->addRecipient(new Device($addresat));
        $message->setNotification($note)
            ->setData(['data' => $aditionalData]);

        $response = $client->send($message);
        return $response->getStatusCode();
    }

    public function sendTopic($title, $message, $addresat){
        $apiKey = 'AAAAjW1lAu4:APA91bE8tX5X1U-F7LtzYUWVAHXer-sMpjNiOeOD8iQF8DRMgJNeTdliuLBcCkcwOr7JDIsl6q-AfQ8gDNR782lw6yhsWfFn0SD0o4LKITG5b3bkh5x32zVqRfXIDH33ELQt_v1Co3QH';
        $client = new Client();
        $client->setApiKey($apiKey);
        $client->injectHttpClient(new \GuzzleHttp\Client());

        $message = new Message();
        $message->addRecipient(new Topic($addresat));
        //select devices where has 'your-topic1' && 'your-topic2' topics
//        $message->addRecipient(new Topic(['your-topic1', 'your-topic2']));
        $message->setNotification(new Notification('test title', 'testing body'))
            ->setData(array('someId' => 111));

        $response = $client->send($message);
        return $response->getStatusCode();
    }
}