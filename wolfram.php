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
$channel_access_token = "Q5tkb0lfnp4rzWZuRNNvWah1rop2mwIIzQPzNcT8KbrjcfwuHDiF10QiGlOrwVddaincpdzoW+Ov0rnwq2jz0kfGQdk6q6UUqCpjNBtqt6CXnpekIiUtDBlRWvxtoyCJf535JpK/vy3Cr5H1Q5PaOwdB04t89/1O/w1cDnyilFU=";
$channel_secret = "0bf4a9bc1a08b2ffd4f719bb3fc915d1";

// inisiasi objek bot
$httpClient = new CurlHTTPClient($channel_access_token);
$bot = new LINEBot($httpClient, ['channelSecret' => $channel_secret]);

$configs =  [
    'settings' => ['displayErrorDetails' => true],
];
$app = new Slim\App($configs);

// buat route untuk url homepage
$app->get('/', function($req, $res)
{
    print "why the fuck are you here?";
});

// buat route untuk webhook
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

    // kode aplikasi nanti disini
    $data = json_decode($body, true);
    if(is_array($data['events'])){
        foreach ($data['events'] as $event)
        {
            if ($event['type'] == 'message')
            {
                if($event['message']['type'] == 'text')
                {

                    $text = $event['message']['text'];
                    $text = str_replace("+", "plus", $text);

                    
                    if ($event['source']['type'] == 'user'){
                        if (substr($text, 0, 1) == '/'){
                            $result = $bot->replyText($event['replyToken'], 'No need to use / if you talk to me on personal chat ;)');
                        }
                        else {
                            $answer = ask($text);
                            $textMessageBuilder = new TextMessageBuilder($answer);
                            $result = $bot->replyMessage($event['replyToken'], $textMessageBuilder);
                        }
                    }
                    
                    else{
                        if ($text == '/leave'){
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
                                $answer = ask($text);
                                $textMessageBuilder = new TextMessageBuilder($answer);
                                $result = $bot->replyMessage($event['replyToken'], $textMessageBuilder);
                            }
                        }
                    }

                    return $response->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());

                }
            }
        }
    }

});

                function ask($question){
                        $question2 = str_replace(" ", "+", $question);
                        

                        $curl = curl_init();
                        curl_setopt_array($curl, array(
                            CURLOPT_RETURNTRANSFER => 1,
                            CURLOPT_URL => 'https://api.wolframalpha.com/v2/result?appid=WV83LL-Y38YUR5AW9&i='.$question2,
                            CURLOPT_USERAGENT => 'Codular Sample cURL Request'
                            ));
                    
                        $curl_response = curl_exec($curl);
                        curl_close($curl);         

                        return $curl_response;           
                }

$app->run();