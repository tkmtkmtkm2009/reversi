<?php

namespace App\Lib;

use App\GomokuUserStatus;
use App\GomokuUserResult;
use App\GomokuLog;
use Carbon\Carbon;

class Gomoku
{
    public function getUserStatus($user_id)
    {
        return GomokuUserStatus::firstOrCreate(['user_id' => $user_id]);
    }

    public function startGomoku($user_id, $board)
    {
        $gomoku_user_status = GomokuUserStatus::where(['user_id' => $user_id])->first();
        $gomoku_user_status->progress = 1;
        $gomoku_user_status->turn = 0;
        $gomoku_user_status->board = $board;
        $gomoku_user_status->save();
    }

    public function endGomoku($user_id)
    {
        $gomoku_user_status = GomokuUserStatus::where(['user_id' => $user_id])->first();
        $gomoku_user_status->progress = 0;
        $gomoku_user_status->level = 0;
        $gomoku_user_status->turn = 0;
        $gomoku_user_status->board = NULL;
        $gomoku_user_status->save();
    }

    public function updateBoard($user_id, $board)
    {
        $gomoku_user_status = GomokuUserStatus::where(['user_id' => $user_id])->first();
        $gomoku_user_status->board = $board;
        $gomoku_user_status->save();
    }

    public function updateTurn($user_id, $level, $turn)
    {
        $gomoku_user_status = GomokuUserStatus::where(['user_id' => $user_id])->first();
        $gomoku_user_status->level = $level;
        $gomoku_user_status->turn = $turn;
        $gomoku_user_status->save();
    }

    public function updateUserResult($user_id, $level, $turn, $result)
    {
        // $gomoku_user_result = GomokuUserResult::firstOrNew(['user_id' => $user_id, 'level' => $level]);
        $gomoku_user_result = GomokuUserResult::where(['user_id' => $user_id, 'level' => $level, 'turn' => $turn])->first();
        if (empty($gomoku_user_result)) {
            $gomoku_user_result = new GomokuUserResult;
            $gomoku_user_result->user_id = $user_id;
            $gomoku_user_result->level = $level;
            $gomoku_user_result->turn = $turn;
            $gomoku_user_result->save();
            $gomoku_user_result = GomokuUserResult::where(['user_id' => $user_id, 'level' => $level, 'turn' => $turn])->first();
        }
        if ($result == 1) {
            $gomoku_user_result->increment('win');
        } else if ($result == -1) {
            $gomoku_user_result->increment('lose');
        } else {
            $gomoku_user_result->increment('draw');
        }
        $gomoku_user_result->save();
    }

    public function insertGomokuLog($user_id, $progress, $level, $turn, $board)
    {
        $gomoku_log = new GomokuLog;
        $gomoku_log->user_id = $user_id;
        $gomoku_log->progress = $progress;
        $gomoku_log->level = $level;
        $gomoku_log->turn = $turn;
        $gomoku_log->board = $board;
        $gomoku_log->created_at = Carbon::now();
        $gomoku_log->save();
    }


    // ----------------------------------

    public function canAttack($board, $x, $y) {
        return $board[$x][$y] == 0;
    }
    public function canAttackAll($board, $player) {
        foreach ($board as $i => $item) {
            foreach ($item as $j => $v) {
                if ($v == 0) {
                    $judgment = $this->judge($board, $i, $j, $player);//並びの判定
                    if ($judgment != -1) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
    public function nextPlayer($player) {
        return $player == 1 ? -1 : 1;
    }

    public function get_infobox($x, $y, $player, $board, $infobox, $level, $d = 0) {

        $gomoku_width = \Config::get('const.gomoku_width');

        // 右下がり方向サーチ
        for ($count = -5; $count <= 5; $count++){
            $x_point = $x + $count;
            $y_point = $y + $count;
            if ($x_point >= 0 && $y_point >= 0 && $x_point <= $gomoku_width-1 && $y_point <= $gomoku_width-1){
                if ($board[$x_point][$y_point] == 0){
                    $infobox[$x_point][$y_point] = $this->condition($board, $x_point, $y_point, $player, $infobox, $level, $d);
                }
            }
        }

        // 垂直方向サーチ
        for ($count = -5; $count <= 5; $count++){
            $y_point = $y + $count;
            if ($y_point >= 0 && $y_point <= $gomoku_width-1){
                if ($board[$x][$y_point] == 0){
                    $infobox[$x][$y_point] = $this->condition($board, $x, $y_point, $player, $infobox, $level, $d);
                }
            }
        }

        // 水平方向サーチ
        for ($count = -5; $count <= 5; $count++){
            $x_point = $x + $count;
            if ($x_point >= 0 && $x_point <= $gomoku_width-1){
                if ($board[$x_point][$y] == 0){
                    $infobox[$x_point][$y] = $this->condition($board, $x_point, $y, $player, $infobox, $level, $d);
                }
            }
        }

        // 右上がり方向サーチ
        for ($count = -5; $count <= 5; $count++){
            $x_point = $x + $count;
            $y_point = $y - $count;
            if ($x_point >= 0 && $y_point >= 0 && $x_point <= $gomoku_width-1 && $y_point <= $gomoku_width-1){
                if ($board[$x_point][$y_point] == 0){
                    $infobox[$x_point][$y_point] = $this->condition($board, $x_point, $y_point, $player, $infobox, $level, $d);
                }
            }
        }

        // 正方形サーチ
        if ($level == 3) {
            for ($count_x = -5; $count_x <= 5; $count_x++){
                for ($count_y = -5; $count_y <= 5; $count_y++){
                    if ($count_x == 0 || $count_y == 0 || $count_x == $count_y || $count_x + $count_y == 0) { // 上で調査済み
                        continue;
                    }
                    $x_point = $x + $count_x;
                    $y_point = $y + $count_y;
                    if ($x_point >= 0 && $y_point >= 0 && $x_point <= $gomoku_width-1 && $y_point <= $gomoku_width-1){
                        if ($board[$x_point][$y_point] == 0 && $infobox[$x_point][$y_point] == 911){ // 四四、四三ではなくなっている可能性があるため
                            $infobox[$x_point][$y_point] = $this->condition($board, $x_point, $y_point, $player, $infobox, $level, $d);
                        }
                    }
                }
            }
        }

        return $infobox;
    }

    /////盤面の順位を調べる
    public function condition($board, $x, $y, $player, $infobox, $level, $d)
    {
        $opponent = $this->nextPlayer($player);

        if ($this->weight_set($board, $x, $y, $player, 0, null, null) == 1) return -1; // 三三禁止
        if ($this->weight_set($board, $x, $y, $player, 6, null, null) == 1) return 999; // 自分五
        if ($this->weight_set($board, $x, $y, $opponent, 6, null, null) == 1) return 998; // 相手五
        if ($this->weight_set($board, $x, $y, $player, 4, 2, 0) == 1) return 997; // 自分棒四(両方空いている)
        if ($this->weight_set($board, $x, $y, $player, 5, null, null) == 1) return 996; // 自分四四、四三
        if ($this->weight_set($board, $x, $y, $opponent, 4, 2, 0) == 1) // 相手棒四
            return $this->sub1_condition($board, $x, $y, $player) + 960;
        if ($this->weight_set($board, $x, $y, $opponent, 5, null, null) == 1) return 950; // 相手四四、四三


        $ret = 0;
        if ($level == 3) {
            $ret = $this->level3_condition($board, $x, $y, $player, $infobox, $level, $d);
        } elseif ($level == 2) {
            $ret = $this->level2_condition($board, $x, $y, $player, $infobox, $level);
        } else {
            $ret = $this->level1_condition($board, $x, $y, $player, $infobox, $level);
        }
        if ($ret) return $ret;


        if ($this->weight_set($board, $x, $y, $opponent, 3, 2, 0) == 1)
            return $this->sub1_condition($board, $x, $y, $player) + 650;
        if ($this->weight_set($board, $x, $y, $opponent, 3, 2, 1) == 1)
            return $this->sub1_condition($board, $x, $y, $player) + 600;
        if ($this->weight_set($board, $x, $y, $opponent, 4, 1, 0) == 1)
            return $this->sub1_condition($board, $x, $y, $player) + 550;
        if ($this->weight_set($board, $x, $y, $opponent, 4, 2, 1) == 1)
            return $this->sub1_condition($board, $x, $y, $player) + 500;
        if ($this->weight_set($board, $x, $y, $opponent, 4, 1, 1) == 1)
            return $this->sub1_condition($board, $x, $y, $player) + 450;

        if ($this->weight_set($board, $x, $y, $player, 2, 2, 0) == 1)
            return $this->sub2_condition($board, $x, $y, $player) + 400;
        if ($this->weight_set($board, $x, $y, $player, 2, 2, 1) == 1)
            return $this->sub2_condition($board, $x, $y, $player) + 350;
        if ($this->weight_set($board, $x, $y, $player, 3, 1, 0) == 1)
            return $this->sub2_condition($board, $x, $y, $player) + 300;
        if ($this->weight_set($board, $x, $y, $player, 3, 1, 1) == 1)
            return $this->sub2_condition($board, $x, $y, $player) + 250;
        if ($this->weight_set($board, $x, $y, $opponent, 2, 2, 0) == 1)
            return $this->sub2_condition($board, $x, $y, $player) + 200;
        if ($this->weight_set($board, $x, $y, $opponent, 2, 2, 1) == 1)
            return $this->sub2_condition($board, $x, $y, $player) + 150;
        if ($this->weight_set($board, $x, $y, $opponent, 3, 1, 0) == 1)
            return $this->sub2_condition($board, $x, $y, $player) + 100;
        if ($this->weight_set($board, $x, $y, $opponent, 3, 1, 1) == 1)
            return $this->sub2_condition($board, $x, $y, $player) + 50;
        return 0;
    }

    // 上級 盤面の順位を調べる
    public function level3_condition($board, $x, $y, $player, $infobox, $level, $d)
    {
        $opponent = $this->nextPlayer($player);
        if ($this->weight_set($board, $x, $y, $player, 3, 2, 0) == 1 ||
            $this->weight_set($board, $x, $y, $player, 3, 2, 1) == 1 ||
            $this->weight_set($board, $x, $y, $player, 4, 1, 0) == 1 ||
            $this->weight_set($board, $x, $y, $player, 4, 2, 1) == 1 ||
            $this->weight_set($board, $x, $y, $player, 4, 1, 1) == 1
        ) {
            if ($d < 1) {
                $tmp_board = $board;
                $tmp_infobox = $infobox;
                $tmp_board[$x][$y] = $player;
                $tmp_infobox = $this->get_infobox($x, $y, $player, $tmp_board, $tmp_infobox, $level, $d+1);
                $tmp_infobox[$x][$y] = -5;

                foreach ($tmp_board as $i => $item) {
                    foreach ($item as $j => $v) {
                        if ($v == 0) {
                            if ($tmp_infobox[$i][$j] == 999 || $tmp_infobox[$i][$j] == 997) { // 五、棒四にならないために相手置く
                                $tmp_board[$i][$j] = $opponent;
                                $tmp_infobox = $this->get_infobox($i, $j, $player, $tmp_board, $tmp_infobox, $level, $d+1);
                                $tmp_infobox[$i][$j] = -5;
                                break;
                            }
                        }
                    }
                }

                foreach ($tmp_board as $i => $item) {
                    foreach ($item as $j => $v) {
                        if ($v == 0) {
                            if ($tmp_infobox[$i][$j] == 996) { // 四四、四三 があるか
                                return 911;
                            }
                        }
                    }
                }
            }
            return $this->level2_condition($board, $x, $y, $player, $infobox, $level);
        }
        return 0;
    }

    // 中級 盤面の順位を調べる
    public function level2_condition($board, $x, $y, $player, $infobox, $level)
    {
        if ($this->weight_set($board, $x, $y, $player, 3, 2, 0) == 1)
            return $this->sub2_condition($board, $x, $y, $player) + 900;
        if ($this->weight_set($board, $x, $y, $player, 3, 2, 1) == 1)
            return $this->sub2_condition($board, $x, $y, $player) + 850 ;
        if ($this->weight_set($board, $x, $y, $player, 4, 1, 0) == 1)
            return $this->sub2_condition($board, $x, $y, $player) + 800;
        if ($this->weight_set($board, $x, $y, $player, 4, 2, 1) == 1)
            return $this->sub2_condition($board, $x, $y, $player) + 750;
        if ($this->weight_set($board, $x, $y, $player, 4, 1, 1) == 1)
            return $this->sub2_condition($board, $x, $y, $player) + 700;

        return 0;
    }

    // 初級 盤面の順位を調べる
    public function level1_condition($board, $x, $y, $player, $infobox, $level)
    {
        if ($this->weight_set($board, $x, $y, $player, 3, 2, 0) == 1 ||
            $this->weight_set($board, $x, $y, $player, 3, 2, 1) == 1 ||
            $this->weight_set($board, $x, $y, $player, 4, 1, 0) == 1 ||
            $this->weight_set($board, $x, $y, $player, 4, 2, 1) == 1 ||
            $this->weight_set($board, $x, $y, $player, 4, 1, 1) == 1
        ) {
            return 900;
        }

        return 0;
    }

    /////盤面の順位を調べるsub1
    public function sub1_condition($board, $x, $y, $player)
    {
        $opponent = $this->nextPlayer($player);

        if ($player == 1){
            if ($this->weight_set($board, $x, $y, $player, 3, 2, 0) == 1) return 20;
            if ($this->weight_set($board, $x, $y, $player, 3, 2, 1) == 1) return 19;
            if ($this->weight_set($board, $x, $y, $player, 4, 1, 0) == 1) return 18;
            if ($this->weight_set($board, $x, $y, $player, 4, 2, 1) == 1) return 17;
            if ($this->weight_set($board, $x, $y, $player, 4, 1, 1) == 1) return 16;
            if ($this->weight_set($board, $x, $y, $player, 2, 2, 0) == 1) return 15;
            if ($this->weight_set($board, $x, $y, $opponent, 3, 2, 0) == 1) return 14;
            if ($this->weight_set($board, $x, $y, $opponent, 3, 2, 1) == 1) return 13;
            if ($this->weight_set($board, $x, $y, $opponent, 4, 1, 0) == 1) return 12;
            if ($this->weight_set($board, $x, $y, $opponent, 4, 2, 1) == 1) return 11;
            if ($this->weight_set($board, $x, $y, $opponent, 4, 1, 1) == 1) return 10;
            if ($this->weight_set($board, $x, $y, $opponent, 2, 2, 0) == 1) return 9;
            if ($this->weight_set($board, $x, $y, $player, 3, 1, 0) == 1) return 8;
            if ($this->weight_set($board, $x, $y, $opponent, 3, 1, 0) == 1) return 7;
            if ($this->weight_set($board, $x, $y, $player, 3, 1, 1) == 1) return 6;
            if ($this->weight_set($board, $x, $y, $opponent, 3, 1, 1) == 1) return 5;
            if ($this->weight_set($board, $x, $y, $player, 2, 2, 1) == 1) return 4;
            if ($this->weight_set($board, $x, $y, $opponent, 2, 2, 1) == 1) return 3;
            if ($this->weight_set($board, $x, $y, $player, 2, 1, 0) == 1) return 2;
            if ($this->weight_set($board, $x, $y, $opponent, 2, 1, 0) == 1) return 1;
        } else {
            if ($this->weight_set($board, $x, $y, $player, 3, 2, 0) == 1) return 20;
            if ($this->weight_set($board, $x, $y, $player, 3, 2, 1) == 1) return 19;
            if ($this->weight_set($board, $x, $y, $player, 4, 1, 0) == 1) return 18;
            if ($this->weight_set($board, $x, $y, $player, 4, 2, 1) == 1) return 17;
            if ($this->weight_set($board, $x, $y, $player, 4, 1, 1) == 1) return 16;
            if ($this->weight_set($board, $x, $y, $opponent, 3, 2, 0) == 1) return 15;
            if ($this->weight_set($board, $x, $y, $opponent, 3, 2, 1) == 1) return 14;
            if ($this->weight_set($board, $x, $y, $opponent, 4, 1, 0) == 1) return 13;
            if ($this->weight_set($board, $x, $y, $opponent, 4, 2, 1) == 1) return 12;
            if ($this->weight_set($board, $x, $y, $opponent, 4, 1, 1) == 1) return 11;
            if ($this->weight_set($board, $x, $y, $opponent, 2, 2, 0) == 1) return 10;
            if ($this->weight_set($board, $x, $y, $player, 2, 2, 0) == 1) return 9;
            if ($this->weight_set($board, $x, $y, $opponent, 3, 1, 0) == 1) return 8;
            if ($this->weight_set($board, $x, $y, $player, 3, 1, 0) == 1) return 7;
            if ($this->weight_set($board, $x, $y, $opponent, 3, 1, 1) == 1) return 6;
            if ($this->weight_set($board, $x, $y, $player, 3, 1, 1) == 1) return 5;
            if ($this->weight_set($board, $x, $y, $opponent, 2, 2, 1) == 1) return 4;
            if ($this->weight_set($board, $x, $y, $player, 2, 2, 1) == 1) return 3;
        }
        return 0;
    }
    /////盤面の順位を調べるsub2
    public function sub2_condition($board, $x, $y, $player)
    {
        $opponent = $this->nextPlayer($player);

        if ($player == 1){
            if ($this->weight_set($board, $x, $y, $player, 2, 2, 0) == 1) return 10;
            if ($this->weight_set($board, $x, $y, $opponent, 2, 2, 0) == 1) return 9;
            if ($this->weight_set($board, $x, $y, $player, 3, 1, 0) == 1) return 8;
            if ($this->weight_set($board, $x, $y, $opponent, 3, 1, 0) == 1) return 7;
            if ($this->weight_set($board, $x, $y, $player, 3, 1, 1) == 1) return 6;
            if ($this->weight_set($board, $x, $y, $opponent, 3, 1, 1) == 1) return 5;
            if ($this->weight_set($board, $x, $y, $player, 2, 2, 1) == 1) return 4;
            if ($this->weight_set($board, $x, $y, $opponent, 2, 2, 1) == 1) return 3;
            if ($this->weight_set($board, $x, $y, $player, 2, 1, 0) == 1) return 2;
            if ($this->weight_set($board, $x, $y, $opponent, 2, 1, 0) == 1) return 1;
        } else {
            if ($this->weight_set($board, $x, $y, $opponent, 2, 2, 0) == 1) return 10;
            if ($this->weight_set($board, $x, $y, $player, 2, 2, 0) == 1) return 9;
            if ($this->weight_set($board, $x, $y, $opponent, 3, 1, 0) == 1) return 8;
            if ($this->weight_set($board, $x, $y, $player, 3, 1, 0) == 1) return 7;
            if ($this->weight_set($board, $x, $y, $opponent, 3, 1, 1) == 1) return 6;
            if ($this->weight_set($board, $x, $y, $player, 3, 1, 1) == 1) return 5;
            if ($this->weight_set($board, $x, $y, $opponent, 2, 2, 1) == 1) return 4;
            if ($this->weight_set($board, $x, $y, $player, 2, 2, 1) == 1) return 3;
        }
        return 0;
    }

    // 桝目のウエイト決定
    public function weight_set($board, $x, $y, $player, $number, $end, $tobi)
    {
        $line = $this->narabi($board, $x, $y, $player);
        if ($number == 0){
            if ($this->three($line) == -1) return 1;
        }
        if ($number == 6){
            if ($this->five($line) == 6) return 1;
        }
        if ($number == 5){
            if ($this->four($line) >= 4) return 1;
        }
        for ($count = 0; $count < 4; $count++){
            if ($line[$count]['count'] == $number && $line[$count]['end_condition'] == $end && $line[$count]['tobi'] == $tobi) return 1;
        }
        return 0;
    }

    /////右下がり、右上がり、横、縦、各列判定
    public function narabi($board, $x, $y, $player)
    {
        $gomoku_width = \Config::get('const.gomoku_width');

        $line = array();
        // 判定ライン１（右下がり）
        $start = $x - $y;
        if ($start < 0) $start = 0;
        $end = $x - $y + $gomoku_width-1;
        if ($end > $gomoku_width-1) $end = $gomoku_width-1;
        $constant = $x - $y;
        $hit_point = $y;
        if ($constant < 0) $hit_point = $x;
        $number = 0;
        for ($count = $start; $count <= $end; $count++){
            $box[$number] = $board[$count][$count - $constant];
            $number++;
        }
        $line[0] = $this->line_judg($hit_point, $end - $start, $player, $box);

        // 判定ライン２（右上がり）
        $start = $x + $y - ($gomoku_width-1);
        if ($start < 0) $start = 0;
        $end = $x + $y;
        if ($end > $gomoku_width-1) $end = $gomoku_width-1;
        $constant = $x + $y;
        $hit_point = $x;
        if ($constant > $gomoku_width-1) $hit_point = $gomoku_width-1 - $y;
        $number = 0;
        for ($count = $start; $count <= $end; $count++){
            $box[$number] = $board[$count][$constant - $count];
            $number++;
        }
        $line[1] = $this->line_judg ($hit_point, $end - $start, $player, $box);

        // 判定ライン３（横）
        $hit_point = $x;
        for ($count = 0; $count <= $gomoku_width-1; $count++){
            $box[$count] = $board[$count][$y];
        }
        $line[2] = $this->line_judg($hit_point, $gomoku_width-1, $player, $box);

        // 判定ライン４（縦）
        $hit_point = $y;
        for ($count = 0; $count <= $gomoku_width-1; $count++){
            $box[$count] = $board[$x][$count];
        }
        $line[3] = $this->line_judg ($hit_point, $gomoku_width-1, $player, $box);

        return $line;
    }

    /////縦、横、右下がり、右上がり、各列展開判定
    public function line_judg($hit_point, $end, $player, $box)
    {
        $tobi = 0;//飛び空間の状態０＝空き無し、１＝空き有り
        $end_condition = 0;//両端の状態０＝閉塞、１＝片端空、２＝両端空
        $start = $hit_point;//石が置かれたポイント

        while (true) {//並びのカウント開始位置を見つける
            if ($start == 0) break;//碁盤の端か
            $start--;// カウントを次に進める
            if ($box[$start] == 0){//現ポイントは空き
                if ($tobi == 0){//１回目の空き
                    if ($start == 0){//碁盤の端
                        $start++;
                        $end_condition++;
                        break;
                    }
                    if ($box[$start - 1] == 0){//空きの次も空き
                        $start++;
                        $end_condition++;
                        break;
                    }
                    if ($box[$start - 1] == $player)//空きの次は自石
                        $tobi = 1;
                    else {//空きの次は相手石
                        $start++;
                        $end_condition++;
                        break;
                    }
                } else {//２回目の空き
                    $start++;
                    $end_condition++;
                    break;
                }
            }
            if ($box[$start] != 0 && $box[$start] != $player){//現ポイントは相手石
                $start++;
                break;
            }
        }

        // 開始点から自石の並びをカウントする
        $count = 0;
        $tobi = 0;
        $box[$hit_point] = $player;
        for ($number = $start; $number <= $end; $number++){
            if ($box[$number] == $player){//現ポイントは自石
                    $count++;
            }
            if ($box[$number] == 0){//現ポイントは空き
                if ($tobi == 0){//１回目の空き
                    if ($number == $end){//碁盤の端
                        $end_condition++;
                        break;
                    }
                    if ($box[$number + 1] == 0){//空きの次も空き
                        $end_condition++;
                        break;
                    }
                    if ($box[$number + 1] == $player){//空きの次は自石
                        $tobi = 1;
                    } else {//空きの次は相手石
                        $end_condition++;
                        break;
                    }
                } else {//２回目の空き
                    $end_condition++;
                    break;
                }
            }
            if ($box[$number] != 0 && $box[$number] != $player) // 現ポイントは相手石
                break;
        }
        // 飛び６以上の５並び補正
        if ($count >= 6 && $tobi == 1){
            $sub_count = 0;
            for ($number = $start; $number <= $start + $count; $number++){
                if ($box[$number] == $player){
                    $sub_count++;
                }
                else break;
            }
            if ($sub_count == 5){
                $count = 5;
                $tobi = 0;
            }
            if ($sub_count == ($count - 5)){
                $count = 5;
                $tobi = 0;
            }
        }
        return array('tobi'=>$tobi,'end_condition'=>$end_condition,'count'=>$count);//戻り値の決定
    }

    /////五並び
    public function five($line)
    {
        $judgment = 0;
        for ($count = 0; $count < 4; $count++){
            if ($line[$count]['count'] == 5 && $line[$count]['tobi'] != 1){
                $judgment = 6;//五並び（勝ち）
                break;
            }
        }
        return $judgment;
    }

    /////四並び、四・四並び、四・三並び
    public function four($line)
    {
        $judgment = 0;
        for ($count = 0; $count < 4; $count++){
            if ( $line[$count]['count'] == 4  && $line[$count]['end_condition'] != 0){
                $judgment = 3;//四並び
                for ($sub_count = 0; $sub_count < 4; $sub_count++){
                    if ($line[$sub_count]['count'] == 3 && $line[$sub_count]['end_condition'] == 2 ){
                        $judgment = 4;//四・三並び
                        break;
                    }
                }
                for ($sub_count = $count + 1; $sub_count < 4; $sub_count++){
                    if ( $line[$sub_count]['count'] == 4 && $line[$sub_count]['end_condition'] != 0){
                        $judgment = 5;//四・四並び
                        break;
                    }
                }
            break;
            }
        }
        return $judgment;
    }

    /////三並びまたは三・三並び
    public function three($line)
    {
        $judgment = 0;
        for ($count = 0; $count < 4; $count++){
            if ($line[$count]['count'] == 3  && $line[$count]['end_condition'] == 2){
                $judgment = 2;//三並び
                if ($line[$count]['tobi'] == 1) $judgment = 1;//飛び三
                for ($sub_count = $count + 1; $sub_count < 4; $sub_count++){
                    if ($line[$sub_count]['count'] == 3 && $line[$sub_count]['end_condition'] == 2){
                        $judgment = -1;//三・三並び
                        break;
                    }
                }
            break;
            }
        }
        return $judgment;
    }

    // 並びを判定する
    public function judge($board, $x, $y, $player)
    {
        $judgment = 0;

        $line = $this->narabi($board, $x, $y, $player);

        $judgment = $this->five($line);
        if ($judgment == 0){
            $judgment = $this->four($line);
            if ($judgment == 0){
                $judgment = $this->three($line);
            }
        }

        // if ($judgment == 5) $temp = "四・四";
        // if ($judgment == 4) $temp = "四・三";
        // if ($judgment == 3) $temp = "四";
        // if ($judgment == 2) $temp = "三";
        // if ($judgment == 1) $temp = "飛び三";

        return $judgment;
    }

    public function getBestXY($board, $infobox, $player) {
        $ret = array('put' => 0);

        $max = 0;
        $put = array();
        foreach ($board as $i => $item) {
            foreach ($item as $j => $v) {
                if ($v == 0) {
                    if ($infobox[$i][$j] > $max) {
                        $max = $infobox[$i][$j];
                        $put = array();
                        $put[] = array('x'=>$i,'y'=>$j);
                    } else if ($infobox[$i][$j] == $max) {
                        $put[] = array('x'=>$i,'y'=>$j);
                    }
                }
            }
        }

        if (count($put)) {
            $rand = mt_rand(0, count($put) - 1);
            $ret['x'] = $put[$rand]['x'];
            $ret['y'] = $put[$rand]['y'];
            $ret['put'] = 1;
        }
        return $ret;
    }
}
