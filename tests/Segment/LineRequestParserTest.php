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

use Imomushi\Worker\Segment\LineRequestParser;

/**
 * Class LineRequestParserTest
 *
 * @package Imomushi\Worker\Tests
 */

class LineRequestParserTest extends \PHPUnit_Framework_TestCase
{
    /*
     * @vars
     */
    private $target;
    public function setUp()
    {
        $this -> target = new LineRequestParser();

    }

    public function tearDown()
    {
    }

    public function testConstruct()
    {
        $this -> assertInstanceOf(
            'Imomushi\Worker\Segment\LineRequestParser',
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
        //non object
        $result = $this -> target -> execute(null);
        $this -> assertEquals(
            ['content' => []],
            $result
        );

        //object but doesn't have 'body' property
        $result = $this -> target -> execute(new \stdClass());
        $this -> assertEquals(
            ['content' => []],
            $result
        );

        //normal case
        $arguments = new \stdClass();
        $body = new \stdClass();
        $body -> result = [
            ['content' => ['test' => 'test']],
        ];
        $arguments -> body = json_encode($body);
        $result = $this -> target -> execute($arguments);
        $content = new \stdClass();
        $content -> test = 'test';
        $this -> assertEquals(
            ['content' => $content],
            $result
        );
    }
}
