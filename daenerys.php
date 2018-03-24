<?php
require __DIR__ . '/vendor/autoload.php';

use \LINE\LINEBot;
use \LINE\LINEBot\HTTPClient\CurlHTTPClient;
use \LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
use \LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use \LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use \LINE\LINEBot\MessageBuilder\ImageMessageBuilder;
use \LINE\LINEBot\SignatureValidator as SignatureValidator;

// set false for production
$pass_signature = true;

// set LINE channel_access_token and channel_secret
$channel_access_token = "2Nqq58W2Dw2PFUwOnzXTD3dJWafiXtkG3duIwJgEpiFOKm7/pGfDKKOhPxB2JTr1gFk0hnZcX80M/FhqMTAhqEa1owo89dvxXC+WwoIb/5nfvlh7NR/B1WrCCXyYkG5jixq2ZJuhQSB3c0j5vhAAkQdB04t89/1O/w1cDnyilFU=";
$channel_secret = "7fa4abe066f7d84b9c86d423db5c2f6c";

// inisiasi objek bot
$httpClient = new CurlHTTPClient($channel_access_token);
$bot = new LINEBot($httpClient, ['channelSecret' => $channel_secret]);

$configs =  [
    'settings' => ['displayErrorDetails' => true],
];
$app = new Slim\App($configs);

// route untuk webhook
$app->post('/webhook', function ($request, $response) use ($bot, $pass_signature)
{
    // get request body and line signature header
    $body        = file_get_contents('php://input');
    $signature = isset($_SERVER['HTTP_X_LINE_SIGNATURE']) ? $_SERVER['HTTP_X_LINE_SIGNATURE'] : '';

    // log body and signature
    file_put_contents('php://stderr', 'Body: '.$body);

    if($pass_signature === false)
    {
        // is LINE_SIGNATURE exists in request header?
        if(empty($signature)){
            return $response->withStatus(400, 'Signature not set');
        }

        // is this request comes from LINE?
        if(! SignatureValidator::validateSignature($body, $channel_secret, $signature)){
            return $response->withStatus(400, 'Invalid signature');
        }
    }

    // kode aplikasi
    $data = json_decode($body, true);
    if(is_array($data['events'])){
        foreach ($data['events'] as $event)
        {
            if ($event['type'] == 'message')
            {
                if($event['message']['type'] == 'text')
                {

                    $text = $event['message']['text'];
                    
                    if ($event['source']['type'] == 'user'){

                        if (substr($text, 0, 1) == '/'){
                            $result = $bot->replyText($event['replyToken'], 'GI PIRLI PIKI / KILI NGIMING LIWIT PC');
                        }
                        else {
                            $imageUrl2 = make_meme($text);
                            $imageMessageBuilder = new ImageMessageBuilder($imageUrl2, $imageUrl2);
                            $result = $bot->replyMessage($event['replyToken'], $imageMessageBuilder);
                        }
                    }
                    
                    else{
                        if ($text == '#leave'){
                            if($event['source']['type'] == 'group'){
                                $groupId = $event['source']['groupId'];
                                $result = $bot->leaveGroup($groupId);

                            }
                            elseif ($event['source']['type'] == 'room') {
                                $roomId = $event['source']['roomId'];
                                $result = $bot->leaveRoom($roomId);
                            }
                        }
                        else{
                            if (substr($text, 0, 1) == '/'){
                                $text = ltrim($text, '/');
                                $imageUrl2 = make_meme($text);
                                $imageMessageBuilder = new ImageMessageBuilder($imageUrl2, $imageUrl2);
                                $result = $bot->replyMessage($event['replyToken'], $imageMessageBuilder);
                            }
                        }
                    }

                        $userId = $event['source']['userId'];
                        $profileResult = $bot->getProfile($userId);

                        $profile = " ";
                        $displayName = " ";
                        $pictureUrl = " ";

                        if ($profileResult->isSucceeded()) {
                            $profile = $profileResult->getJSONDecodedBody();
                            $displayName =  $profile['displayName'];
                            $pictureUrl =  $profile['pictureUrl'];
                        }

                        $toWrite = $displayName.'< sent >'.$text.'< at >'.$event['timestamp'].'<br>';
                        //file_put_contents('logs/logs-Daenerys.txt', $toWrite, FILE_USE_INCLUDE_PATH | FILE_APPEND);

                        $myFile = fopen("logs-Daenerys.txt", "a+")or die("Unable to open file!");
                        fwrite($myFile, $toWrite);
                        fclose($myFile);

                    return $response->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());

                }
            }
        }
    }
    
});

                function make_meme ($text){
                    $charArr = str_split($text);
                    $text2 = "";

                    for($i=0; $i<= strlen($text); $i++){
                        $char = substr($text, $i,1);
                        if($char=='a' or $char=='u' or $char=='e' or $char=='o' or $char=='i'){
                            $charArr[$i] = 'i';
                            
                        }

                        if($char=='A' or $char=='U' or $char=='E' or $char=='O' or $char=='I'){
                            $charArr[$i] = 'I';
                            
                        }

                        $text2 .= $charArr[$i];
                    }

                        $service_url = 'https://api.imgflip.com/caption_image';
                        $curl = curl_init($service_url);
                        $curl_post_data = array(
                                'template_id' => '35456817',
                                'username' => 'hardlexander',
                                'password' => '12345abcde',
                                'text1' => $text2
                        );

                        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($curl, CURLOPT_POST, true);
                        curl_setopt($curl, CURLOPT_POSTFIELDS, $curl_post_data);
                        $curl_response = curl_exec($curl);
                        $decoded = json_decode($curl_response, true);
                        
                        curl_close($curl);

                    $imageUrl = $decoded['data']['url'];
                    $imageUrl2 = str_replace("http", "https", $imageUrl);
                    file_put_contents('php://stderr', $imageUrl2);

                    return $imageUrl2;
                }

$app->run();