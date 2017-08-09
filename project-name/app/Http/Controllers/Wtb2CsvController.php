<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class Wtb2CsvController extends Controller
{

    private $input_dir_path  = '';
    private $output_file_path = '';
    private $wtb_file_info  = array();

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {

        $is_correct_arg = $this->checkArg();
        if (!$is_correct_arg)
        {
            echo '処理を中断します。' . PHP_EOL;
            return -1;
        }

        echo '===============================' . PHP_EOL;
        echo 'wtbをCSVへ変換開始' . PHP_EOL;
        echo '===============================' . PHP_EOL;

        echo '===============================' . PHP_EOL;
        echo '変換元フォルダ：' . $this->input_dir_path . PHP_EOL;
        echo '変換後ファイル：' . $this->output_file_path . PHP_EOL;
        echo '===============================' . PHP_EOL;


        // wtbファイル名を抽出する
        $this->analyseWtbFiles();

        // 実変換処理
        $this->convert();

        echo '===============================' . PHP_EOL;
        echo 'wtbをCSVへ変換完了' . PHP_EOL;
        echo '===============================' . PHP_EOL;

        return;
        return view('wtb2csv/index');
    }

    private function analyseWtbFiles()
    {
        $dir = $this->input_dir_path;

        $check_dirs = array($dir);

        while( $check_dirs ) {
            $dir_path = $check_dirs[0] ;
            if( is_dir ( $dir_path ) && $handle = opendir ( $dir_path ) ) {
                while( ( $file = readdir ( $handle ) ) !== false ) {
                    if( in_array ( $file, array('.', '..') ) !== false ) continue ;
                    $path = rtrim ( $dir_path, '/' ) . '/' . $file ;

                    if ( filetype ( $path ) === 'dir' ) {
                        $check_dirs[] = $path ;
                    } else {
                        // $file: ファイル名
                        // $path: ファイルのパス
                        // echo $file . ' (' . $path . ')' . '\n' ;
                        if(strpos($file, '.wtb') !== false){
                            //'abcd'のなかに'bc'が含まれている場合
                            $this->wtb_file_info[$path] = $file;
                        }
                    }
                }
            }

            array_shift( $check_dirs ) ;
        }
    }

    private function convert()
    {
        $sql = array();
        $is_success = true;
        $count = 0;

        foreach($this->wtb_file_info as $wtb_file_path => $file_name)
        {
            $this->generateSql($wtb_file_path, $file_name);
            echo ' 変換完了：' . $file_name  . PHP_EOL;
            ++$count;
        }

        return;
    }

    private function generateSql($wtb_file_path, $file_name)
    {
        $reversi = new \App\Lib\Reversi;
        //--------------------------------------------------
        // ファイルの読み込み
        //--------------------------------------------------
        // http://hp.vector.co.jp/authors/VA015468/platina/algo/append_a.html
        $offset = 16;  // バイナリファイル先頭の16バイト目から取得する
        $head2 = 6;   // 6バイト取得する
        $head3 = 1;   // 1バイト取得する
        $head4 = 1;   // 1バイト取得する
        $maxlen = 60;   // 60バイト取得する

        $fp = fopen($wtb_file_path, 'rb');
        if ($fp === false) {
            throw Exception("Can not open file: $jpg");
        }
        fseek($fp, $offset); // ヘッダ

        $fw = fopen($this->output_file_path."/".$file_name.".csv", "w");

        $count = 0;
        while (!feof($fp)) {

            if ($count > 2000) {
                break;
            }
            $count++;

            // ボードの初期化
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
            $player = 1;

            $tmp = fread($fp, $head2);
            $result = fread($fp, $head3);
            $result = ord($result);
            $win = 0;
            $skip = false;

            if ($result > $n * $n / 2) {
                $win = 1;
            } else if ($result < $n * $n / 2) {
                $win = -1;
            } else {
                $skip = true;
            }
            $tmp = fread($fp, $head4);

            for ($i = 0; $i < $maxlen; $i++) {
                $data = fread($fp, 1);
                $data = ord($data);
                if ($data == 0 || $skip) {
                    continue;
                }

                $x = floor($data / 10) - 1;
                $y = $data % 10 - 1;

                if (!$reversi->canAttack($board, $x, $y, $player)) {
                    $player = $reversi->nextPlayer($player);
                }

                // ひっくり返る箇所取得
                $list = $reversi->listVulnerableCells($board, $x, $y, $player);
                $board[$x][$y]['state'] = $player;
                foreach ($list as $v) {
                    $board[$v[0]][$v[1]]['state'] = $player;
                }

                // board と 置き場所 CSV に保存
                if ($i > 20) {
                    $line = array();
                    // 黒
                    foreach ($board as $p_i => $item) {
                        foreach ($item as $p_j => $v) {
                            if ($v['state'] == $player) {
                                // $line[] = $v['state'] * $player;
                                $line[] = 1;
                            } else {
                                $line[] = 0;
                            }
                        }
                    }
                    // 白
                    foreach ($board as $p_i => $item) {
                        foreach ($item as $p_j => $v) {
                            if ($v['state'] * -1 == $player) {
                                $line[] = 1;
                            } else {
                                $line[] = 0;
                            }
                        }
                    }
                    if ($player == $win) {
                        $line[] = 1;
                        $line[] = 0;
                    } else {
                        $line[] = 0;
                        $line[] = 1;
                    }
                    // $line[] = $x;
                    // $line[] = $y;
                    // for ($p_i = 0; $p_i < REVERSI_WIDTH; $p_i++) {
                    //  for ($p_j = 0; $p_j < REVERSI_WIDTH; $p_j++) {
                    //      if ($p_i == $x && $p_j == $y) {
                    //          $line[] = 1;
                    //      } else {
                    //          $line[] = 0;
                    //      }
                    //  }
                    // }
                    fputcsv($fw, $line);
                }


                $list = $reversi->listVulnerableCells($board, $x, $y, $player);
                $board[$x][$y]['state'] = $player;
                foreach ($list as $v) {
                    $board[$v[0]][$v[1]]['state'] = $player;
                }

                $player = $reversi->nextPlayer($player);
            }

        }
        fclose($fp);
        fclose($fw);
        return;
    }

    private function checkArg()
    {
        // if (!isset($this->args[0]) || !isset($this->args[1]))
        // {
        //  echo '【エラー】：引数が不正です、第一引数に変換元フォルダ、第二引数に変換後ファイルパスを指定して下さい' . PHP_EOL;
        //  return false;
        // }

        $this->input_dir_path   = '../database/reversi/wtb';
        $this->output_file_path = '../database/reversi/csv';

        return true;
    }
}
