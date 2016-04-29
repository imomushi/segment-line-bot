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

/**
 * Class LineEchoBack
 *
 * @package Imomushi\Worker
 */
class LineEchoBack
{
    /**
     * @var
     */

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
        $bot = new LINEBot($config, new GuzzleHTTPClient($config));
        $content = (array)$arguments->content;
        $res = $bot->sendText([$content['from']], $content['text']);
        if ($res instanceof \LINE\LINEBot\Response\SucceededResponse) {
            return ['status' => true];
        }
        return ['status' => false];
    }
}
