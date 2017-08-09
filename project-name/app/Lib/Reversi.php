<?php

namespace App\Lib;

use App\ReversiUserStatus;
use App\ReversiUserResult;
use App\ReversiLog;
use Carbon\Carbon;

class Reversi
{
    public function getUserStatus($user_id)
    {
        return ReversiUserStatus::firstOrCreate(['user_id' => $user_id]);
    }

    public function startReversi($user_id, $board)
    {
        $reversi_user_status = ReversiUserStatus::find($user_id);
        $reversi_user_status->progress = 1;
        $reversi_user_status->turn = 0;
        $reversi_user_status->board = $board;
        $reversi_user_status->save();
    }

    public function endReversi($user_id)
    {
        $reversi_user_status = ReversiUserStatus::find($user_id);
        $reversi_user_status->progress = 0;
        $reversi_user_status->level = 0;
        $reversi_user_status->turn = 0;
        $reversi_user_status->board = NULL;
        $reversi_user_status->save();
    }

    public function updateBoard($user_id, $board)
    {
        $reversi_user_status = ReversiUserStatus::find($user_id);
        $reversi_user_status->board = $board;
        $reversi_user_status->save();
    }

    public function updateTurn($user_id, $level, $turn)
    {
        $reversi_user_status = ReversiUserStatus::find($user_id);
        $reversi_user_status->level = $level;
        $reversi_user_status->turn = $turn;
        $reversi_user_status->save();
    }

    public function updateUserResult($user_id, $level, $result)
    {
        // $reversi_user_result = ReversiUserResult::firstOrNew(['user_id' => $user_id, 'level' => $level]);
        $reversi_user_result = ReversiUserResult::where(['user_id' => $user_id, 'level' => $level])->first();
        if (empty($reversi_user_result)) {
            $reversi_user_result = new ReversiUserResult;
            $reversi_user_result->user_id = $user_id;
            $reversi_user_result->level = $level;
            $reversi_user_result->save();
            $reversi_user_result = ReversiUserResult::where(['user_id' => $user_id, 'level' => $level])->first();
        }
        if ($result == 1) {
            $reversi_user_result->increment('win');
        } else if ($result == -1) {
            $reversi_user_result->increment('lose');
        } else {
            $reversi_user_result->increment('draw');
        }
        $reversi_user_result->save();
    }

    public function insertReversiLog($user_id, $progress, $level, $turn, $board)
    {
        $reversi_log = new ReversiLog;
        $reversi_log->user_id = $user_id;
        $reversi_log->progress = $progress;
        $reversi_log->level = $level;
        $reversi_log->turn = $turn;
        $reversi_log->board = $board;
        $reversi_log->created_at = Carbon::now();
        $reversi_log->save();
    }


    // ----------------------------------


    public function listAttackingMoves($board, $player)
    {
        $tmp_board = $board;
        foreach ($tmp_board as $i => $item) {
            foreach ($item as $j => $v) {
                $tmp_board[$i][$j]['put'] = $this->canAttack($board, $i, $j, $player) ? 1 : 0;
            }
        }
        return $tmp_board;
    }

    public function canPut($board)
    {
        $can_put = false;
        foreach ($board as $i => $item) {
            foreach ($item as $j => $v) {
                if ($v['put']) {
                    $can_put = true;
                }
            }
        }
        return $can_put;
    }

    public function canAttack($board, $x, $y, $player)
    {
        return count($this->listVulnerableCells($board, $x, $y, $player));
    }

    public function nextPlayer($player)
    {
        return $player == 1 ? -1 : 1;
    }

    public function listVulnerableCells($board, $x, $y, $player)
    {
        $vulnerableCells = array();

        if ($board[$x][$y]['state'] != 0)
            return $vulnerableCells;

        $n = count($board);
        $opponent = $this->nextPlayer($player);
        for ($dx = -1; $dx <= 1; $dx++) {
            for ($dy = -1; $dy <= 1; $dy++) {
                if ($dx == 0 && $dy == 0) {
                    continue;
                }
                for ($i = 1; $i < $n; $i++) {
                    $nx = $x + $i * $dx;
                    $ny = $y + $i * $dy;
                    if ($nx < 0 || $n <= $nx || $ny < 0 || $n <= $ny)
                        break;
                    $cell = $board[$nx][$ny]['state'];
                    if ($cell == $player && 2 <= $i) {
                        for ($j = 1; $j < $i; $j++) {
                            $vulnerableCells[] = array($x + $j * $dx, $y + $j * $dy);
                        }
                        break;
                    }
                    if ($cell != $opponent) {
                        break;
                    }
                }
            }
        }

        return $vulnerableCells;
    }

    public function checkResult($board, $player)
    {
        $ret= 0;
        $player_sum = 0;
        $opponent_sum = 0;
        $opponent = $this->nextPlayer($player);
        foreach ($board as $i => $item) {
            foreach ($item as $j => $v) {
                if ($v['state'] == $player) {
                    $player_sum++;
                } else if ($v['state'] == $opponent) {
                    $opponent_sum++;
                }
            }
        }
        if ($player_sum > $opponent_sum) {
            $ret = 1;
        } else if ($opponent_sum > $player_sum) {
            $ret = -1;
        }
        return $ret;
    }

    public function getScoreFromAI($board, $player)
    {
        $board_str = '';
        if ($player == -1) {
            // 黒 <-> 白 変換
            foreach ($board as $i => $item) {
                foreach ($item as $j => $v) {
                    if ($v['state'] != 0) {
                        $board[$i][$j]['state'] *= $player;
                    }
                }
            }
        }
        foreach ($board as $i => $item) {
            foreach ($item as $j => $v) {
                // 1手目 F5(5行6列目)に置いたようにするために変換

            }
        }
        foreach ($board as $i => $item) {
            foreach ($item as $j => $v) {
                if ($v['state'] == 1) {
                    $board_str .= '1';
                } else if ($v['state'] == -1) {
                    $board_str .= '2';
                } else {
                    $board_str .= '0';
                }
            }
        }

        $base_url = 'http://tensorflow:8889/api/reversi?board=';
        $response = file_get_contents($base_url.$board_str);
        // 結果はjson形式で返されるので
        $result = json_decode($response,true);

        return $result['results'];
    }

    public function getBestXYFromAIScore($score)
    {
        $ret = array();
        $x = 0;
        $y = 0;
        $max_score = 0;
        foreach ($score as $i => $item) {
            foreach ($item as $j => $v) {
                if ($max_score == 0) {
                    $x = $i;
                    $y = $j;
                    $max_score = $v[0];
                } else if ($v > $max_score) {
                    $x = $i;
                    $y = $j;
                    $max_score = $v[0];
                }
            }
        }

        if ($max_score) {
            $ret = array('x' => $x, 'y' => $y);
        }
        return $ret;
    }

    public function getBestXY($board, $player, $level, $d = 0)
    {
        // 深層学習(仮)
        $count = 0;
        foreach ($board as $i => $item) {
            foreach ($item as $j => $v) {
                if ($v['state'] != 0) {
                    $count++;
                }
            }
        }
        if ($level == 3 && $count > 20 && $d == 0) {

            $score = array();
            foreach ($board as $i => $item) {
                foreach ($item as $j => $v) {
                    if ($v['put'] == 1) {
                        $tmp_board = $board;
                        $list = $this->listVulnerableCells($tmp_board, $i, $j, $player);
                        $tmp_board[$i][$j]['state'] = $player;
                        foreach ($list as $v) {
                            $tmp_board[$v[0]][$v[1]]['state'] = $player;
                        }
                        $score[$i][$j] = $this->getScoreFromAI($tmp_board, $player);
                    }
                }
            }
            $ai = $this->getBestXYFromAIScore($score);

            $ret = array('put' => 0);
            if ($ai) {
                $ret['put'] = 1;
                $ret['x'] = $ai['x'];
                $ret['y'] = $ai['y'];
            }
            return $ret;
        }


        $ret = array('put' => 0);

        if ($level == 1) {
            $weight = array(
                    array(4,-1,0,0),
                    array(-1,-1,0,0),
                    array(0,0,0,0),
                    array(0,0,0,0),
                );
        } else {
            $weight = array(
                    array(68,-32,53,-8),
                    array(-32,-62,-33,-7),
                    array(53,-33,26,8),
                    array(-8,-7,8,-18),
                );
        }
        $reversi_width = \Config::get('const.reversi_width');
        foreach ($weight as $i => $item) {
            foreach ($item as $j => $v) {
                $weight[$i][$reversi_width-1-$j] = $v;
            }
            $weight[$reversi_width-1-$i] = $weight[$i];
        }
        $weight_num = -100;
        $put = array();
        foreach ($board as $i => $item) {
            foreach ($item as $j => $v) {
                if ($v['put'] == 1) {
                    $ret['put'] = 1;
                    $w = $weight[$i][$j];

                    if ($d < 1) {
                        $tmp_board = $board;
                        $list = $this->listVulnerableCells($tmp_board, $i, $j, $player);
                        $tmp_board[$i][$j]['state'] = $player;
                        foreach ($list as $v) {
                            $tmp_board[$v[0]][$v[1]]['state'] = $player;
                        }
                        $opponent = $this->nextPlayer($player);
                        $tmp_board = $this->listAttackingMoves($tmp_board, $opponent);

                        $tmp_ret = $this->getBestXY($tmp_board, $opponent, $level, $d + 1);

                        if ($tmp_ret['put']) {
                            $w -= $tmp_ret['weight_num'];
                        }
                    }

                    if (empty($put)) {
                        $put[] = array('x' => $i, 'y' => $j);
                        $weight_num = $w;
                    } else if ($w == $weight_num) {
                        $put[] = array('x' => $i, 'y' => $j);
                    } else if ($w > $weight_num) {
                        $put = array();
                        $put[] = array('x' => $i, 'y' => $j);
                        $weight_num = $w;
                    }
                    $ret['weight_num'] = $weight_num;
                }
            }
        }
        if (count($put)) {
            $rand = mt_rand(0, count($put) - 1);
            $ret['x'] = $put[$rand]['x'];
            $ret['y'] = $put[$rand]['y'];
        }
        return $ret;
    }
}
