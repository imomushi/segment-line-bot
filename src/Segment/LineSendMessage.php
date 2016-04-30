<?php
/*
 * This file is part of Worker.
 *
 ** (c) 2016 -  Fumikazu FUjiwara
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Imomushi\Worker\Segment;

use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\GuzzleHTTPClient;

/**
 * Class LineSendMessage
 *
 * @package Imomushi\Worker
 */
class LineSendMessage
{
    /**
     * @var
     */
    public $emitter = null;
    public $currentResponse;

    /**
     * Constructer
     */
    public function __construct()
    {
    }

    public function execute($arguments)
    {
        $config = [
            'channelId' => $arguments->line_channel_id,
            'channelSecret' => $arguments->line_channel_secret,
            'channelMid' => $arguments->line_channel_mid
        ];
        if (!is_null($this -> emitter)) {
            $config = array_merge($config, [
                'emitter' => $this -> emitter
                ]);
        }
        $bot = new LINEBot($config, new GuzzleHTTPClient($config));
        $content = (array)$arguments->content;
        $this -> currentResponse = $bot->sendText([$content['from']], $content['text']);
        if ($this -> currentResponse instanceof \LINE\LINEBot\Response\SucceededResponse) {
            return ['status' => true];
        }
        return ['status' => false];
    }
}
