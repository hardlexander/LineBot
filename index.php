<?php
require __DIR__ . '/vendor/autoload.php';

use \LINE\LINEBot;
use \LINE\LINEBot\HTTPClient\CurlHTTPClient;
use \LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
use \LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use \LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use \LINE\LINEBot\MessageBuilder\ImageMessageBuilder;
use \LINE\LINEBot\MessageBuilder\AudioMessageBuilder;
use \LINE\LINEBot\SignatureValidator as SignatureValidator;

// set false for production
$pass_signature = true;

// set LINE channel_access_token and channel_secret
$channel_access_token = "rImSaAJ2/QQ1g1DsMed21aFbjtpzN24cr/MOuN4hDKh1S9Xgbw3ZS0l+xwezIHZ3nRCvzNz9tZNBY/2jyAhgG0JO/atDfjqwmejErateRBXy23ryOOgCdnyN0OLT1n7OgPLu8DD8mUP+C5RSCW71XQdB04t89/1O/w1cDnyilFU=";
$channel_secret = "b9f090bb5e0707b4d97eb130bd8738fd";

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
    $service_url = 'https://api.imgflip.com/caption_image';
    $curl = curl_init($service_url);
    $curl_post_data = array(
            'template_id' => '35456817',
            'username' => 'hardlexander',
            'password' => '12345abcde',
            'text1' => "hello worlfd"
    );

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $curl_post_data);
    $curl_response = curl_exec($curl);
    $decoded = json_decode($curl_response, true);

    print_r($decoded);
    
    
    curl_close($curl);

    print "ini decoded->{'url'} " .$decoded->{'url'} ."\n";
    print "ini decoded->{'data'}->{'url'} " .$decoded->{'data'}->{'url'} ."\n";
    print "ini curl_response " .$curl_response ."\n";
    print "ini curl_response->url " .$curl_response->url ."\n";    
    print "ini decoded " .$decoded ."\n";
    print "ini decoded[data][url]" .$decoded['data']['url'];
    
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
                    elseif (substr($text, 0, 1) == '*') {
                        $question = ltrim($text, '*');
                        $answer = ask($question);

                       
                        $textMessageBuilder = new TextMessageBuilder($answer);

                        $result = $bot->replyMessage($event['replyToken'], $textMessageBuilder);
                    }
                    elseif (substr($text, 0, 1) == '#') {
                         $result = $bot->replyText($event['replyToken'], 'add @fbc1872j untuk menggunakan bot daenerys bray.');

                     }
                    elseif ($text == '720') {
                         $audio = "https://host123d1qe.cloudconvert.com/download/~8M0F-0-FTn87N6pLsuN94TsxplE";
                         $audioMessage = new AudioMessageBuilder ($audio, 37000);
                        
                        $result = $bot->replyMessage($event['replyToken'], $audioMessage);

                     }
                    else
                     {
                            list($surah, $ayah) = explode(":", $text);
                    
                            if (strpos($ayah,"-")){
                                list($from, $to) = explode("-", $ayah);

                                $arab = "";
                                $latin = "";
                                $indo = "";
                                $audio = "Recitation: ";

                                for($i = $from; $i<=$to; $i++){
                                    $verse = findVerse($surah.":".$i);
                                    if ($verse['code'] == 400){
                                        break;
                                    } else {
                                        $arab = $arab.$verse['data'][0]['text']." (".arabic_w2e($i).") ";
                                        $latin = $latin."(".$i.") ".$verse['data'][1]['text']." ";
                                        $indo = $indo."(".$i.") ".$verse['data'][2]['text']." ";
                                        $audio = $audio."(".$i.") ".$verse['data'][3]['audio']." ";

                                    }
                                }

                            }
                            else{
                                $verse = findVerse($text);
                                $arab = $verse['data'][0]['text'];
                                $latin = $verse['data'][1]['text'];
                                $indo = $verse['data'][2]['text'];
                                $audio = "Recitation: ".$verse['data'][3]['audio'];

                            }
                            
                         $textMessageBuilder1 = new TextMessageBuilder($arab);
                         $textMessageBuilder2 = new TextMessageBuilder($latin);
                         $textMessageBuilder3 = new TextMessageBuilder($indo);
                         $textMessageBuilder4 = new TextMessageBuilder($audio);

                         
                         $multiMessageBuilder = new MultiMessageBuilder();
                         $multiMessageBuilder->add($textMessageBuilder1);
                         $multiMessageBuilder->add($textMessageBuilder2);
                         $multiMessageBuilder->add($textMessageBuilder3);
                         $multiMessageBuilder->add($textMessageBuilder4);

                        $result = $bot->replyMessage($event['replyToken'], $multiMessageBuilder);
                    }

                    return $response->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());

                }
            }
        }
    }

});

                function findVerse($verse){
                        

                        $curl = curl_init();
                        curl_setopt_array($curl, array(
                            CURLOPT_RETURNTRANSFER => 1,
                            CURLOPT_URL => 'http://api.alquran.cloud/ayah/'.$verse.'/editions/quran-simple,en.transliteration,id.indonesian,ar.abdulbasitmurattal',
                            CURLOPT_USERAGENT => 'Codular Sample cURL Request'
                            ));

                        

                        $curl_response = curl_exec($curl);
                        $decoded = json_decode($curl_response, true);
                        curl_close($curl);         

                        return $decoded;           
                }

                function arabic_w2e($str)
                {
                    $arabic_eastern = array('٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩');
                    $arabic_western = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
                    return str_replace($arabic_western, $arabic_eastern, $str);
                }

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