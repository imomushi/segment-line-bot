<?php
/*
 * This file is part of Worker.
 *
 ** (c) 2016 - Fumikazu Fujiwara
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Imomushi\Worker\Tests;

use GuzzleHttp\Event\Emitter;
use GuzzleHttp\Message\Request;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\History;
use GuzzleHttp\Subscriber\Mock;
use Imomushi\Worker\Segment\LineSendMessage;

/**
 * Class LineSendMessageTest
 *
 * @package Imomushi\Worker\Tests
 */

class LineSendMessageTest extends \PHPUnit_Framework_TestCase
{
    /*
     * @vars
     */
    private $target;
    private $tmpFile;
    private $histories;
    public function setUp()
    {
        $this -> tmpFile = tempnam(sys_get_temp_dir(), 'Imomushi.Segment.LineSendMessage.');
        $this -> target = new LineSendMessage();
        $mock = new Mock([
            new Response(
                200,
                [],
                Stream::factory('{"failed":[],"messageId":"1460826285060","timestamp":1460826285060,"version":1}')
            ),
            new Response(
                400,
                [],
                Stream::factory('{"statusCode":"422","statusMessage":"invalid users"}')
            ),
            new Response(
                500,
                [],
                Stream::factory(
                    '{"statusCode":"500","statusMessage":"unexpected error found at call bot api sendMessage"}'
                )
            ),
        ]);

        $this -> histories = new History();
        $emitter = new Emitter();
        $emitter->attach($mock);
        $emitter->attach($this -> histories);
        $this -> target -> emitter = $emitter;

    }

    public function tearDown()
    {
        unlink($this -> tmpFile);
    }

    public function testConstruct()
    {
        $this -> assertInstanceOf(
            'Imomushi\Worker\Segment\LineSendMessage',
            $this -> target
        );
    }

    public function testExecute()
    {
        $this -> assertTrue(
            method_exists(
                $this -> target,
                'execute'
            )
        );
        $arguments = new \stdClass();
        $arguments -> content = array(
            'from' => 'DUMMY_MID',
            'text' => 'hello!',
        );
        $arguments->line_channel_id = '1000000000';
        $arguments->line_channel_secret = 'testsecret';
        $arguments->line_channel_mid ='TEST_MID';
        $this -> assertEquals(
            $this -> target -> execute($arguments),
            ['status' => true]
        );
        $res = $this -> target -> currentResponse;
        $this->assertInstanceOf('\LINE\LINEBot\Response\SucceededResponse', $res);
        /** @var \LINE\LINEBot\Response\SucceededResponse $res */
        $this->assertTrue($res->isSucceeded());
        $this->assertEquals(200, $res->getHTTPStatus());
        $this->assertEmpty($res->getFailed());
        $this->assertEquals('1460826285060', $res->getMessageId());
        $this->assertEquals(1460826285060, $res->getTimestamp());
        $this->assertEquals(1, $res->getVersion());

        $arguments -> content['from'] = 'INVALID_MID';
        $this -> assertEquals(
            $this -> target -> execute($arguments),
            ['status' => false]
        );
        $res = $this -> target -> currentResponse;
        $this->assertInstanceOf('\LINE\LINEBot\Response\FailedResponse', $res);
        /** @var \LINE\LINEBot\Response\FailedResponse $res */
        $this->assertFalse($res->isSucceeded());
        $this->assertEquals(400, $res->getHTTPStatus());
        $this->assertEquals('422', $res->getStatusCode());
        $this->assertEquals('invalid users', $res->getStatusMessage());

        $arguments -> content['from'] = 'DUMMY_MID';
        $arguments -> content['text'] = 'SOMETHING WRONG PAYLOAD';
        $this -> assertEquals(
            $this -> target -> execute($arguments),
            ['status' => false]
        );
        $res = $this -> target -> currentResponse;
        $this->assertInstanceOf('\LINE\LINEBot\Response\FailedResponse', $res);
        /** @var \LINE\LINEBot\Response\FailedResponse $res */
        $this->assertFalse($res->isSucceeded());
        $this->assertEquals(500, $res->getHTTPStatus());
        $this->assertEquals('500', $res->getStatusCode());
        $this->assertEquals('unexpected error found at call bot api sendMessage', $res->getStatusMessage());

        $history = $this -> histories->getIterator()[0];
        /** @var Request $req */
        $req = $history['request'];
        $this->assertEquals($req->getMethod(), 'POST');
        $this->assertEquals($req->getUrl(), 'https://trialbot-api.line.me/v1/events');

        $data = json_decode($req->getBody(), true);
        $this->assertEquals($data['eventType'], 138311608800106203);
        $this->assertEquals($data['to'], ['DUMMY_MID']);
        $this->assertEquals($data['content']['text'], 'hello!');

        $channelIdHeader = $req->getHeaderAsArray('X-Line-ChannelID');
        $this->assertEquals(sizeof($channelIdHeader), 1);
        $this->assertEquals($channelIdHeader[0], '1000000000');

        $channelSecretHeader = $req->getHeaderAsArray('X-Line-ChannelSecret');
        $this->assertEquals(sizeof($channelSecretHeader), 1);
        $this->assertEquals($channelSecretHeader[0], 'testsecret');

        $channelMidHeader = $req->getHeaderAsArray('X-Line-Trusted-User-With-ACL');
        $this->assertEquals(sizeof($channelMidHeader), 1);
        $this->assertEquals($channelMidHeader[0], 'TEST_MID');
    }
}
