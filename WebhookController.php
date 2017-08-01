<?php

use LINE\LINEBot;
use LINE\LINEBot\Constant\HTTPHeader;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\Event\BeaconDetectionEvent;
use LINE\LINsEBot\Event\FollowEvent;
use LINE\LINEBot\Event\JoinEvent;
use LINE\LINEBot\Event\LeaveEvent;
use LINE\LINEBot\Event\MessageEvent;
use LINE\LINEBot\Event\MessageEvent\AudioMessage;
use LINE\LINEBot\Event\MessageEvent\ImageMessage;
use LINE\LINEBot\Event\MessageEvent\LocationMessage;
use LINE\LINEBot\Event\MessageEvent\StickerMessage;
use LINE\LINEBot\Event\MessageEvent\TextMessage;
use LINE\LINEBot\Event\MessageEvent\UnknownMessage;
use LINE\LINEBot\Event\MessageEvent\VideoMessage;
use LINE\LINEBot\Event\PostbackEvent;
use LINE\LINEBot\Event\UnfollowEvent;
use LINE\LINEBot\Event\UnknownEvent;
use LINE\LINEBot\Exception\InvalidEventRequestException;
use LINE\LINEBot\Exception\InvalidSignatureException;

use LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;
use LINE\LINEBot\ImagemapActionBuilder\ImagemapUriActionBuilder;
use LINE\LINEBot\ImagemapActionBuilder\AreaBuilder;
use LINE\LINEBot\ImagemapActionBuilder\ImagemapMessageActionBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
use LINE\LINEBot\MessageBuilder\Imagemap\BaseSizeBuilder;
use LINE\LINEBot\MessageBuilder\ImagemapMessageBuilder;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ConfirmTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder;


/**
 * Class title
 *
 * Class Description
 *
 * @author Yugo Kimura <me@example.com>
 * @since 1.0.0
 */

class WebhookController extends Controller {

        function __construct() {
                parent::__construct();
        }

        function __destruct() {
                parent::__destruct();
        }

        /**
         * TITLE
         *
         * DESCRIPTION
         *
         *
         * @param type text
         * pparam type text
         * @return  type test
         */
        function index($arg){

                $bot = new LINEBot(
                        new CurlHTTPClient(LINE_CHANNEL_ACCESSTOKEN),
                        ['channelSecret' => LINE_CHANNEL_SECRET]
                );

                try {

                        // LINEからの署名を取得 @をつけてエラーを回避
                        $signature = @$_SERVER["HTTP_" . HTTPHeader::LINE_SIGNATURE];

                        // LINEから送信されたパラメータ(JSON)を取得
                        $body = file_get_contents("php://input");

                        // このやりとりのイベント名を取得(テキストなのか、画像なのか、スタンプなのか、、、)
                        $events  = $bot->parseEventRequest($body, $signature);

                        // eventsは配列形式でくるため、配列をループにかける
                        foreach ($events as $event) {


                                // 返信用のワンタイムトークンを取得
                                $reply_token = $event->getReplyToken();
                                $user_id     = $event->getUserId(); // user_idを取得
                                $response        = $bot->getProfile($user_id); // プロフィール名を取得
                                $profile = "";
                                if($response->isSucceeded() ) {
                                        $profile = $response->getJSONDecodedBody();
                                        $profile = $profile['displayName'];
                                }

                                //$user_id = $event->getUserId();       

                                // もしテキストデータだった場合
                                if ($event instanceof TextMessage) {
////////////////////////////////////////////////////////////

                                        // リセット
                                        if ( $body == "リセット" ) {
                                                $this->redis->delete("user_id:$user_id:body");
                                        }

                                        // 送信されてきた文字列を取得
                                        $body = $event->getText();

                                        // テキストメッセージオブジェクトを作成
                                        $echoText = new TextMessageBuilder("入力値:" . $body);
                                        $userIdText = new TextMessageBuilder("ユーザ識別子:" . $user_id);
                                        $profileText = new TextMessageBuilder(">名前:" . $profile);

                                        // 複数個のテキストを返信できるオブジェ>クトを作成
                                        $SendMessage = new MultiMessageBuilder();
                                        // 作成したテキストオブジェクトを追加
                                        $SendMessage->add($profileText);
                                        $SendMessage->add($userIdText);
                                        $SendMessage->add($echoText);

                                        // 参考
                                        // 前回のメッセージがある場合
                                        if(  $this->redis->get("user_id:$user_id:body") ) {
                                                // 前回のメッセージでテキストオ>ブジェクト作成
                                                $previousText = new TextMessageBuilder("前回の入力値:" . $this->redis->get("user_id:$user_id:body"));
                                                // 追加
                                                $SendMessage->add($previousText);

                                        }

                                        // ここから
                                        if( $body == "退勤する" ) {                                                                              
                                                //キーワードが入力されてからの処理
                                                $replyText = new TextMessageBuilder("電車の時刻を表示します。");
                                                $SendMessage->add($replyText);
                                        }

                                        // if( $this->redis->get("user_id:$user_id:body") == "おなかすいた" ) {
                                        //         // 以前の文字列がおなかすいたの>場合
                                        //         if( $body == $food ) {
                                        //                 $replyText = new TextMessageBuilder($food . "ですね\nわかりました。");
                                        // }

                                        //　今回入力された内容を保存
                                        $this->redis->set("user_id:$user_id:body", $body);

                                        // 一度だけ利用できるreply_tokenを利用して相手に返信
                                        $response = $bot->replyMessage($reply_token, $SendMessage);

/////////////////////////////////////////////////////////////
                                }
                                // もしステッカー(すたんぷ)だった場合
                                // else if ($event instanceof StickerMessage) {
                                //         $columns = [];
                                //         $lists = [1,2,3,4,5];
                                //         foreach ($lists as $list) {
                                //                 // カルーセルのリンクボタンを作>成
                                //                 $action1 = new UriTemplateActionBuilder("クリックしてねA", 'https://bot-6415.chat-ai.tk/' );
                                //                 $action2 = new UriTemplateActionBuilder("クリックしてねB", 'https://bot-6415.chat-ai.tk/' );
                                //                 // カルーセルのアイテムにリンク
                                //                 $column = new CarouselColumnTemplateBuilder("タイトル(40文字以内)", "追加文", 'https://bot-6415.chat-ai.tk/img/sample.jpg', [$action1, $action2]);
                                //                 $columns[] = $column;
                                //         }
                                //         //カルーセルの作成
                                //         $carousel = new CarouselTemplateBuilder($columns);
                                //         $carousel_message = new TemplateMessageBuilder("メッセージのタイトル", $carousel);
                                //         $response = $bot->replyMessage($reply_token, $carousel_message);
                                // }
                        }

                } catch (Exception $e) {
                        print $e->getLine() . "\n";
                        print $e->getMessage();
                }
        }

}
