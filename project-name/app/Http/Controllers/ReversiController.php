<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use DB;

class ReversiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        return view('reversi/index');
    }

    public function reversiSwf()
    {
        $user_id = Auth::id();

        $reversi = new \App\Lib\Reversi;
        $status = $reversi->getUserStatus($user_id);

        if ($status['progress'] == 0) {
            return redirect('reversi/index');
        }

        $board = json_decode($status['board'], true);

        $can_put = true;
        if ($status['turn'] != 0) {
            $board = $reversi->listAttackingMoves($board, $status['turn']);
            $can_put = $reversi->canPut($board);
        }

        $level_list = array(
            1 => array('card_id' => 1, 'name' => '初級'),
            2 => array('card_id' => 2, 'name' => '中級'),
            3 => array('card_id' => 3, 'name' => '上級'),
        );

        $blade = array();
        $blade['avatar'] = array('card_id' => 0);
        $blade['level_list'] = $level_list;

        $blade['turn']       = $status['turn'];
        $blade['level']      = $status['level'];
        $blade['board']      = $board;
        $blade['can_put']    = $can_put;

        $blade['return_url'] = 'reversi/index';

        return view('reversi/reversiSwf')->with($blade);
    }

    public function doStartReversi()
    {
        $user_id = Auth::id();

        $reversi = new \App\Lib\Reversi;
        $status = $reversi->getUserStatus($user_id);

        if ($status['progress'] > 0) {
            return redirect('reversi/reversiSwf');
        }

        $board = array();
        $n = \Config::get('const.reversi_width');
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $board[$i][$j] = array('state' => 0, 'put' => 0);
            }
        }
        $board[$n/2-1][$n/2-1]['state'] = -1;
        $board[$n/2][$n/2]['state'] = -1;
        $board[$n/2-1][$n/2]['state'] = 1;
        $board[$n/2][$n/2-1]['state'] = 1;

        $ret = $reversi->startReversi($user_id, json_encode($board));

        return redirect('reversi/reversiSwf');
    }

    public function doSetTurn(Request $request)
    {
        $this->validate($request, [
            'level' => 'required',
            'turn' => 'required',
        ]);
        $level = $request->input('level');
        $turn = $request->input('turn');

        $user_id = Auth::id();

        $reversi = new \App\Lib\Reversi;
        $status = $reversi->getUserStatus($user_id);

        if ($status['progress'] == 0 || $status['turn'] != 0) {
            return array("error" => 1);
        }

        $result = array(
                'my_put' => array(),
                'my_tip_over' => array(),
                'opponent_put' => array(),
                'opponent_tip_over' => array(),
                'next_put' => array(),
                'talk' => array(),
            );

        $board = json_decode($status['board'], true);

        $change_turn = false;
        // if ($level == 3 && $turn  == 1) {
        //     $change_turn = true;
        //     $turn  = -1;
        // }
        if ($turn != 1) {
            $turn  = -1;
            $opponent = $reversi->nextPlayer($turn);

            $rand = mt_rand(0,3);
            $n = \Config::get('const.reversi_width');
            if ($level == 3) {
                // $first = array(array(0,0),array(0,$n-1),array($n-1,0),array($n-1,$n-1));
                $first = array(array($n/2-1,$n/2-2),array($n/2-2,$n/2-1),array($n/2,$n/2+1),array($n/2+1,$n/2));
                $rand = 2;
            } else {
                $first = array(array($n/2-1,$n/2-2),array($n/2-2,$n/2-1),array($n/2,$n/2+1),array($n/2+1,$n/2));
            }
            $x = $first[$rand][0];
            $y = $first[$rand][1];
            $list = $reversi->listVulnerableCells($board, $x, $y, $opponent);
            $board[$x][$y]['state'] = $opponent;
            foreach ($list as $v) {
                $board[$v[0]][$v[1]]['state'] = $opponent;
            }
            $result['opponent_put'] = array($x, $y);
            $result['opponent_tip_over'] = $list;
        }

        $board = $reversi->listAttackingMoves($board, $turn);
        $next_put = array();
        foreach ($board as $i => $item) {
            foreach ($item as $j => $v) {
                if ($v['put'] == 1) {
                    $next_put[] = array($i, $j);
                }
            }
        }
        $result['next_put'] = $next_put;

        DB::transaction(function () use($reversi, $user_id, $level, $turn, $board) {
            $reversi->updateTurn($user_id, $level, $turn);
            if ($turn == -1) {
                $reversi->updateBoard($user_id, json_encode($board));
            }
        });

        $level_list = array(
            1 => array('card_id' => 1, 'name' => '初級', 'talk' => 'よ…よろしくお願いします'),
            2 => array('card_id' => 2, 'name' => '中級', 'talk' => '負けませんよ！'),
            3 => array('card_id' => 3, 'name' => '上級', 'talk' => '先手必勝！必殺！！'),
            );

        $result['talk'] = $level_list[$level]['talk'];
        return array("result" => $result, "level_list" => $level_list, "turn"=>$turn, "change_turn"=>$change_turn);
    }

    public function doPut(Request $request)
    {
        $this->validate($request, [
            'x' => 'required',
            'y' => 'required',
        ]);
        $x = $request->input('x');
        $y = $request->input('y');

        $user_id = Auth::id();

        $reversi = new \App\Lib\Reversi;
        $status = $reversi->getUserStatus($user_id);

        if ($status['progress'] == 0) {
            return array("error" => 1);
        }

        $board = json_decode($status['board'], true);

        if (!$reversi->canAttack($board, $x, $y, $status['turn'])) {
            return array("error" => 1);
        }

        $list = $reversi->listVulnerableCells($board, $x, $y, $status['turn']);
        $board[$x][$y]['state'] = $status['turn'];
        foreach ($list as $v) {
            $board[$v[0]][$v[1]]['state'] = $status['turn'];
        }

        $result = array(
                'my_put' => array($x, $y),
                'my_tip_over' => $list,
                'opponent_put' => array(),
                'opponent_tip_over' => array(),
                'next_put' => array(),
                'talk' => array(),
            );

        $opponent = $reversi->nextPlayer($status['turn']);

        $listAttackingMoves = $reversi->listAttackingMoves($board, $opponent);


        $opponent_put = false;
        $bestXY = $reversi->getBestXY($listAttackingMoves, $opponent, $status['level']);

        if ($bestXY['put']) {
            $opponent_put = true;
            $x = $bestXY['x'];
            $y = $bestXY['y'];
        }

        $n = \Config::get('const.reversi_width');
        $rand = mt_rand(1, 100);
        if (!$opponent_put) {
            $talk_list = array(
                1 => array('パスだよ'),
                2 => array('パス(；o；)'),
                3 => array('パスだ！'),
            );
            $rand2 = mt_rand(1, count($talk_list[$status['level']]));
            $result['talk'] = $talk_list[$status['level']][$rand2-1];
        } else if (($x==0 && $y==0) || ($x==0 && $y==$n-1) || ($x==$n-1 && $y==0) || ($x==$n-1 && $y==$n-1)) {
            $talk_list = array(
                1 => array('…ありがとう','…角取るよ'),
                2 => array('角っ♪','角っ♡'),
                3 => array('当然の結果です','ここです！','角取ります！'),
            );
            $rand2 = mt_rand(1, count($talk_list[$status['level']]));
            $result['talk'] = $talk_list[$status['level']][$rand2-1];
        } else if ($rand <= 10) {
            $talk_list = array(
                1 => array('ここに置くよ','ここだよ'),
                2 => array('！！','！？',),
                3 => array('ここはどうだ！？','そこか！？'),
            );
            $plus_list = array(
                // '共通追加セリフ１',
                // '共通追加セリフ２',
            );
            for ($i=1;$i<=3;$i++) {
                foreach ($plus_list as $v) {
                    $talk_list[$i][] = $v;
                }
            }
            $rand2 = mt_rand(1, count($talk_list[$status['level']]));
            $result['talk'] = $talk_list[$status['level']][$rand2-1];
        }

        if ($opponent_put) {
            $list = $reversi->listVulnerableCells($board, $x, $y, $opponent);
            $board[$x][$y]['state'] = $opponent;
            foreach ($list as $v) {
                $board[$v[0]][$v[1]]['state'] = $opponent;
            }
            $result['opponent_put'] = array($x, $y);
            $result['opponent_tip_over'] = $list;
        }

        $board = $reversi->listAttackingMoves($board, $status['turn']);
        $next_put = array();
        $end = true;
        foreach ($board as $i => $item) {
            foreach ($item as $j => $v) {
                if ($v['put'] == 1) {
                    $next_put[] = array($i, $j);
                }
                if ($v['state'] == 0) {
                    $end = false;
                }
            }
        }
        $result['next_put'] = $next_put;

        if ($end || (!$opponent_put && empty($next_put))) {
            $end = true;
            $result['result'] = $reversi->checkResult($board, $status['turn']);

            DB::transaction(function () use($reversi, $user_id, $status, $result) {
                $reversi->endReversi($user_id);
                $reversi->updateUserResult($user_id, $status['level'], $result['result']);
            });
        } else {
            $reversi->updateBoard($user_id, json_encode($board));
        }


        if ($end) {
            $result['talk'] = array();
            if ($status['level'] == 1 && $result['result'] == 1) {
                $result['talk'] = "まいりました";
            } else if ($status['level'] == 2 && $result['result'] == 1) {
                $result['talk'] = "まいりました(；o；)";
            } else if ($status['level'] == 3 && $result['result'] == 1) {
                $result['talk'] = "生意気いってすいませんでした";
            }
            return array("end" => 1, "result" => $result);
        } else {
            return array("result" => $result);
        }
    }

    public function doPass()
    {

        $user_id = Auth::id();

        $reversi = new \App\Lib\Reversi;
        $status = $reversi->getUserStatus($user_id);

        if ($status['progress'] == 0) {
            return array("error" => 1);
        }

        $board = json_decode($status['board'], true);
        $can_put = $reversi->canPut($board);
        if ($can_put) {
            echo json_encode(array("error" => 1));
            exit;
        }
                $list = array();

        $result = array(
                'my_put' => array(),
                'my_tip_over' => $list,
                'opponent_put' => array(),
                'opponent_tip_over' => array(),
                'next_put' => array(),
                'talk' => array(),
            );

        $opponent = $reversi->nextPlayer($status['turn']);

        $listAttackingMoves = $reversi->listAttackingMoves($board, $opponent);

        $opponent_put = false;
        $bestXY = $reversi->getBestXY($listAttackingMoves, $opponent, $status['level']);

        $x = null;
        $y = null;
        if ($bestXY['put']) {
            $opponent_put = true;
            $x = $bestXY['x'];
            $y = $bestXY['y'];
        }

        $n = \Config::get('const.reversi_width');
        if (($x==0 && $y==0) || ($x==0 && $y==$n-1) || ($x==$n-1 && $y==0) || ($x==$n-1 && $y==$n-1)) {
            $talk_list = array(
                1 => array('ごめんなさい。こういうときどんな顔をすればいいかわからないの。'),
                2 => array('♪'),
                3 => array('当然の結果です'),
            );
            $result['talk'] = $talk_list[$status['level']];
        }

        if ($opponent_put) {
            $list = $reversi->listVulnerableCells($board, $x, $y, $opponent);
            $board[$x][$y]['state'] = $opponent;
            foreach ($list as $v) {
                $board[$v[0]][$v[1]]['state'] = $opponent;
            }
            $result['opponent_put'] = array($x, $y);
            $result['opponent_tip_over'] = $list;
        }

        $board = $reversi->listAttackingMoves($board, $status['turn']);
        $next_put = array();
        foreach ($board as $i => $item) {
            foreach ($item as $j => $v) {
                if ($v['put'] == 1) {
                    $next_put[] = array($i, $j);
                }
            }
        }
        $result['next_put'] = $next_put;

        $end = false;

        if (!$opponent_put && empty($next_put)) {
            $end = true;
            $result['result'] = $reversi->checkResult($board, $status['turn']);
            DB::transaction(function () use($reversi, $user_id, $status, $result) {
                $reversi->endReversi($user_id);
                $reversi->updateUserResult($user_id, $status['level'], $result['result']);
            });
        } else {
            $reversi->updateBoard($user_id, json_encode($board));
        }

        if ($end) {
            return array("end" => 1, "result" => $result);
        } else {
            return array("result" => $result);
        }
    }
}
