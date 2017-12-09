<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use DB;

class GomokuController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        return view('gomoku/index');
    }

    public function gomokuSwf()
    {
        $user_id = Auth::id();

        $gomoku = new \App\Lib\Gomoku;
        $status = $gomoku->getUserStatus($user_id);

        if ($status['progress'] == 0) {
            return redirect('gomoku/index');
        }

        $tmp = json_decode($status['board'], true);
        $board = $tmp['board'] ?? array();
        $infobox = $tmp['infobox'] ?? array();

        $pass = !$gomoku->canAttackAll($board, $status['turn']);

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
        $blade['pass']       = $pass;

        $blade['return_url'] = 'gomoku/index';

        return view('gomoku/gomokuSwf')->with($blade);
    }

    public function doStartGomoku()
    {
        $user_id = Auth::id();

        $gomoku = new \App\Lib\Gomoku;
        $status = $gomoku->getUserStatus($user_id);

        if ($status['progress'] > 0) {
            return redirect('gomoku/gomokuSwf');
        }

        $board = array();
        $infobox = array();
        $n = \Config::get('const.gomoku_width');
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $board[$i][$j] = 0;
                $infobox[$i][$j] = -5;
            }
        }

        $ret = $gomoku->startGomoku($user_id, json_encode(array('board'=>$board,'infobox'=>$infobox)));

        return redirect('gomoku/gomokuSwf');
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

        $gomoku = new \App\Lib\Gomoku;
        $status = $gomoku->getUserStatus($user_id);

        if ($status['progress'] == 0 || $status['turn'] != 0) {
            return array("error" => 1);
        }

        $result = array(
                'my_put' => array(),
                'opponent_put' => array(),
                'talk' => array(),
            );

        $tmp = json_decode($status['board'], true);
        $board = $tmp['board'] ?? array();
        $infobox = $tmp['infobox'] ?? array();

        $change_turn = false;
        // if ($level == 3 && $turn  == 1) {
        //     $change_turn = true;
        //     $turn  = -1;
        // }
        $n = \Config::get('const.gomoku_width');
        $half_n = floor($n / 2);
        if ($turn == 1) {
            // 先攻
            $first = array($half_n,$half_n);
            $x = $first[0];
            $y = $first[1];
            $board[$x][$y] = $turn;
            $infobox[$x][$y] = -5;
            $result['my_put'] = array($x, $y);

            $opponent = $gomoku->nextPlayer($turn);
            $infobox = $gomoku->get_infobox($x, $y, $opponent, $board, $infobox, $level);
            $rand = rand(0, 7);
            $first = array(array($half_n-1,$half_n-1),array($half_n-1,$half_n),array($half_n-1,$half_n+1),array($half_n,$half_n-1),array($half_n,$half_n+1),array($half_n+1,$half_n-1),array($half_n+1,$half_n),array($half_n+1,$half_n+1));
            $x = $first[$rand][0];
            $y = $first[$rand][1];
            $board[$x][$y] = $opponent;
            $result['opponent_put'] = array($x, $y);
            $infobox = $gomoku->get_infobox($x, $y, $opponent, $board, $infobox, $level);
        } else {
            // 後攻
            $turn  = -1;
            $opponent = $gomoku->nextPlayer($turn);

            $first = array($half_n,$half_n);
            $x = $first[0];
            $y = $first[1];
            $board[$x][$y] = $opponent;
            $infobox[$x][$y] = -5;
            $result['opponent_put'] = array($x, $y);
            $infobox = $gomoku->get_infobox($x, $y, $opponent, $board, $infobox, $level);
        }

        DB::transaction(function () use($gomoku, $user_id, $level, $turn, $board, $infobox) {
            $gomoku->updateTurn($user_id, $level, $turn);
            $gomoku->updateBoard($user_id, json_encode(array('board'=>$board,'infobox'=>$infobox)));
        });

        $level_list = array(
            1 => array('card_id' => 1, 'name' => '初級', 'talk' => 'お控えなされませ♪'),
            2 => array('card_id' => 2, 'name' => '中級', 'talk' => '私に歯向かう事の恐ろしさがお分かりにならぬ様で…'),
            3 => array('card_id' => 3, 'name' => '上級', 'talk' => 'これも何かの縁、手柔らかに頼む…'),
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

        $gomoku = new \App\Lib\Gomoku;
        $status = $gomoku->getUserStatus($user_id);

        if ($status['progress'] == 0) {
            return array("error" => 1);
        }

        $tmp = json_decode($status['board'], true);
        $board = $tmp['board'] ?? array();
        $infobox = $tmp['infobox'] ?? array();

        if (!$gomoku->canAttack($board, $x, $y)) {
            return array("error" => 1);
        }

        $judgment = $gomoku->judge($board, $x, $y, $status['turn']);//並びの判定
        $board[$x][$y] = $status['turn'];

        $result = array(
                'my_put' => array($x, $y),
                'opponent_put' => array(),
                'talk' => array(),
                'pass' => false,
            );

        if ($judgment == -1) {
            echo json_encode(array("error" => 1));
            exit;
        } else if ($judgment == 6) {
            // 勝ち
            $result['result'] = 1;
        } else {

            $opponent = $gomoku->nextPlayer($status['turn']);
            $infobox = $gomoku->get_infobox($x, $y, $opponent, $board, $infobox, $status['level']);
            $infobox[$x][$y] = -5;

// ログ
// $gomoku->insertGomokuLog($user_id, json_encode(array('board'=>$board,'infobox'=>$infobox)), $dbhSub1);

            $opponent_put = false;
            $bestXY = $gomoku->getBestXY($board, $infobox, $opponent);

            if ($bestXY['put']) {
                $opponent_put = true;
                $x = $bestXY['x'];
                $y = $bestXY['y'];
                $judgment = $gomoku->judge($board, $x, $y, $opponent);//並びの判定

                if ($judgment == 6) {
                    $result['result'] = -1;
                }
                if ($judgment == 5) {
                    $talk_list = array(
                        1 => array('四・四'),
                        2 => array('四・四'),
                        3 => array('御台、四・四じゃ♪'),
                    );
                    $rand2 = mt_rand(1, count($talk_list[$status['level']]));
                    $result['talk'] = $talk_list[$status['level']][$rand2-1];
                } else if ($judgment == 4) {
                    $talk_list = array(
                        1 => array('四・三'),
                        2 => array('四・三'),
                        3 => array('御台、四・三じゃ♪'),
                    );
                    $rand2 = mt_rand(1, count($talk_list[$status['level']]));
                    $result['talk'] = $talk_list[$status['level']][$rand2-1];
                } else if ($judgment == 3) {
                    $talk_list = array(
                        1 => array('四'),
                        2 => array('四'),
                        3 => array('御台、四じゃ♪'),
                    );
                    $rand2 = mt_rand(1, count($talk_list[$status['level']]));
                    $result['talk'] = $talk_list[$status['level']][$rand2-1];
                } else if ($judgment == 2) {
                    $talk_list = array(
                        1 => array('三'),
                        2 => array('三'),
                        3 => array('御台、三じゃ♪'),
                    );
                    $rand2 = mt_rand(1, count($talk_list[$status['level']]));
                    $result['talk'] = $talk_list[$status['level']][$rand2-1];
                } else if ($judgment == 1) {
                    $talk_list = array(
                        1 => array('飛び三'),
                        2 => array('飛び三'),
                        3 => array('御台、飛び三じゃ♪'),
                    );
                    $rand2 = mt_rand(1, count($talk_list[$status['level']]));
                    $result['talk'] = $talk_list[$status['level']][$rand2-1];
                }

                $board[$x][$y] = $opponent;

                $infobox = $gomoku->get_infobox($x, $y, $opponent, $board, $infobox, $status['level']);
                $infobox[$x][$y] = -5;

                $result['opponent_put'] = array($x, $y);
            } else {
                if ($gomoku->canAttackAll($board, $status['turn'])) {
                    $result['talk'] = 'パス';
                } else {
                    $result['result'] = 0;
                }
            }

            $result['pass'] = !$gomoku->canAttackAll($board, $status['turn']);
        }

        $rand = mt_rand(1, 100);
        if (empty($result['talk']) && $rand <= 10) {
            // if ($status['flg']) {
            if (true) {
                $talk_list = array(
                    1 => array('お控えなされませ♪'),
                    2 => array('…!'),
                    3 => array('危ないではないか！'),
                );
                // $plus_list = array(
                //  'もしかして…長いセリフ、消えるの早くて読めない？',
                // );
                // for ($i=1;$i<=3;$i++) {
                //  foreach ($plus_list as $v) {
                //      $talk_list[$i][] = $v;
                //  }
                // }
                $rand2 = mt_rand(1, count($talk_list[$status['level']]));
                $result['talk'] = $talk_list[$status['level']][$rand2-1];
            }
        }

        if (isset($result['result'])) {
            DB::transaction(function () use($gomoku, $user_id, $status, $result) {
                $gomoku->endGomoku($user_id);
                $gomoku->updateUserResult($user_id, $status['level'], $status['turn'], $result['result']);
            });
        } else {
            $gomoku->updateBoard($user_id, json_encode(array('board'=>$board,'infobox'=>$infobox)));
        }

        $end = isset($result['result']);
        if ($end) {
            if ($result['result'] == 1) {
                // ランキング
                // $redis = new RedisCommonUtil( 'main', MEMCACHE_NO_ENABLE );
                // $redis_key = AppGomoku::getRedKeyGomokuRanking($status['level'], $status['turn']);
                // $ranking = AppRanking::getRankingDataForRedis($redis_key, $this->userId);
                // $redis->redZIncrBy($redis_key, 1, $this->userId);
            }
            $result['talk'] = array();
            if ($status['level'] == 3 && $result['result'] == 1) {
                $result['talk'] = "まいりました";
            } else if ($status['level'] == 2 && $result['result'] == 1) {
                $result['talk'] = "まいりました";
            } else if ($status['level'] == 1 && $result['result'] == 1) {
                $result['talk'] = "まいりました";
            }
            return array("end" => 1, "result" => $result);
        } else {
            return array("result" => $result);
        }
    }

    public function doPass()
    {
        $user_id = Auth::id();

        $gomoku = new \App\Lib\Gomoku;
        $status = $gomoku->getUserStatus($user_id);

        if ($status['progress'] == 0) {
            return array("error" => 1);
        }

        $tmp = json_decode($status['board'], true);
        $board = $tmp['board'] ?? array();
        $infobox = $tmp['infobox'] ?? array();

        if ($gomoku->canAttackAll($board, $status['turn'])) {
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

        $opponent = $gomoku->nextPlayer($status['turn']);

        $opponent_put = false;
        $bestXY = $gomoku->getBestXY($board, $infobox, $opponent);

        if ($bestXY['put']) {
            $opponent_put = true;
            $x = $bestXY['x'];
            $y = $bestXY['y'];
            $judgment = $gomoku->judge($board, $x, $y, $opponent);//並びの判定

            if ($judgment == 6) {
                $result['result'] = -1;
            }
            $board[$x][$y] = $opponent;

            $infobox = $gomoku->get_infobox($x, $y, $opponent, $board, $infobox, $status['level']);
            $infobox[$x][$y] = -5;

            $result['opponent_put'] = array($x, $y);
        } else {
            $result['result'] = 0;
        }

        $end = isset($result['result']);
        if ($end) {
            DB::transaction(function () use($gomoku, $user_id, $status, $result) {
                $gomoku->endGomoku($user_id);
                $gomoku->setUserResult($user_id, $status['level'], $status['turn'], $result['result']);
            });
        } else {
            $gomoku->updateBoard($user_id, json_encode($board));
        }

        if ($end) {
            return array("end" => 1, "result" => $result);
        } else {
            return array("result" => $result);
        }
    }
}
