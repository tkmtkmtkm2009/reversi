<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Auth::routes();

Route::get('home', 'HomeController@index')->name('home');

Route::get('reversi/index', 'ReversiController@index');
Route::get('reversi/reversiSwf', 'ReversiController@reversiSwf');
Route::get('reversi/doStartReversi', 'ReversiController@doStartReversi');
Route::post('reversi/doSetTurn', 'ReversiController@doSetTurn');
Route::post('reversi/doPut', 'ReversiController@doPut');
Route::post('reversi/doPass', 'ReversiController@doPass');

Route::get('gomoku/index', 'GomokuController@index');
Route::get('gomoku/gomokuSwf', 'GomokuController@gomokuSwf');
Route::get('gomoku/doStartGomoku', 'GomokuController@doStartGomoku');
Route::post('gomoku/doSetTurn', 'GomokuController@doSetTurn');
Route::post('gomoku/doPut', 'GomokuController@doPut');
Route::post('gomoku/doPass', 'GomokuController@doPass');

Route::get('wtb2csv/index', 'Wtb2CsvController@index');