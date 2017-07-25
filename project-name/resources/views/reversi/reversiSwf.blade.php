@extends('layouts.app')

@section('content')
@include('loading')
<div id="btnArea" class="container" style="text-align:left;">
    <div class='stage'>
        <div id="animelim">
            @if ($turn == 0)
                <div id="select_turn" class="mt20">
                    <div>
                        @foreach ($level_list as $level => $item)
                            <div class="select_level"><label class="radio-inline2"><input type="radio" name="level" value="{{ $level }}"
                            @if ($level == 1)
                                checked="checked"
                            @endif
                            ><img src="/img/{{ $item['card_id'] }}.jpg" width="60" height="60"><div class="name">{{ $item['name'] }}</div></label></div>
                        @endforeach
                    </div>
                    <div>
                        <div class="select_turn"><label class="radio-inline3"><input type="radio" name="turn" value="1" checked="checked"><table><tr><td class="cell black"><span class="disc"></span></td></tr></table>先手</label></div>
                        <div class="select_turn"><label class="radio-inline3"><input type="radio" name="turn" value="-1"><table><tr><td class="cell white"><span class="disc"></span></td></tr></table>後手</label></div>
                    </div>
                    <div id="start" class="btn btn-primary">開始</div>
                </div>
            @endif
            <div id="view">
                <div class="pl3 pb5 character">
                    <div class="float-left"><table><tr><td id="enemy_turn" class="cell
                    @if ($turn == 1)
                        white
                    @elseif ($turn == -1)
                        black
                    @endif
                    "><span class="disc"></span></td></tr></table><div id="enemy_turn_name" class="name">
                    @if ($turn == 1)
                        後手
                    @elseif ($turn == -1)
                        先手
                    @endif
                    </div></div>
                    <div class="float-left img-small"><span id="enemy_img">
                        <img class="img-small" src="/img/{{ $level_list[$level]['card_id'] }}.jpg">
                    </span><div id="enemy_name" class="name">{{ $level_list[$level]['name'] }}</div></div>
                    <div id="balloon" class="float-left balloon-left"></div>
                </div>
                <table class="table">
                @foreach ($board as $i => $item)
                    <tr>
                    @foreach ($item as $j => $v)
                        <td id="cell_{{ $i }}_{{ $j }}" class="cell
                        @if ($v['put'])
                            put
                        @elseif ($v['state'] == 1)
                            black
                        @elseif ($v['state'] == -1)
                            white
                        @else
                            empty
                        @endif
                        ">
                            <span class="disc"></span>
                        </td>
                    @endforeach
                    </tr>
                @endforeach
                </table>
                <div class="pl3 pt5 character">
                    <div class="float-left"><table><tr><td id="my_turn" class="cell
                    @if ($turn == 1)
                        black
                    @elseif ($turn == -1)
                        white
                    @endif
                    "><span class="disc"></span></td></tr></table><div id="my_turn_name" class="name">
                    @if ($turn == 1)
                        先手
                    @elseif ($turn == -1)
                        後手
                    @endif
                    </div></div>
                    <div id="my_img" class="float-left img-small">
                        <img class="img-small" src="/img/{{ $avatar['card_id'] }}.jpg">
                    </div>
                </div>
            </div>
            <div id="pass" class="mt10">
                <div class="btn btn-primary btn-pass">パス</div>
            </div>
            <div id="end" class="end">
                <div id="result" class="result_img"></div>
                <div id="nextbutton"><div class="btn btn-primary nextbutton">次へ</div></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('css')
<style type="text/css">
    .stage, .stage #animelim{margin: 0 auto;}
    .nextbutton{
        display: block !important;
        width: 60px;
        margin: 0 auto;
    }
    .img{width: 70px;}
    .img-small{
        max-width: 47px;
        max-height: 47px;
        width: calc((100vmin - 39px)/8);
        height: calc((100vmin - 39px)/8);
    }
    #select_turn{text-align: center;}
    .select_turn{display: inline-block;margin: 20px;}
    .select_level{display: inline-block;width: 100px;}
    .table{
        width: initial !important;
        top: 0;
        bottom: 0;
        left: 0;
        right: 0;
        margin: 0 auto;
        clear:both;
    }
    .float-left{float: left;}
    .pl3{padding-left: 3px;}
    .pt5{padding-top: 5px;}
    .pb5{padding-bottom: 5px;}
    .character{
        width:100%;
        max-width: 377px;
        height: 65px;
        margin: 0 auto;
        position: relative;
    }
    #pass{text-align: center;}
    .end {
        position: relative;
        top: -290px;
    }
    #balloon{display: none;}
    .balloon-left {
        max-width: 68%;
        margin-left: 10px;
        background-color: white;
        padding: 1rem;
        position: absolute;
        border: 1px solid #aaa;
        border-radius: 4px;
    }
    @media (max-width: 499px) {
        .balloon-left {
            left: 28%;
        }
    }
    @media (min-width: 500px) {
        .balloon-left {
            left: 100px;
        }
    }
    .balloon-left:before {
        content: '';
        position: absolute;
        width: 0px;
        height: 0px;
        border-top: 10px solid white;
        border-left: 10px solid transparent;
        border-right: 10px solid transparent;
        -moz-transform: rotate(90deg);
        -webkit-transform: rotate(90deg);
        transform: rotate(90deg);
        top: 15px;
        left: -14px;
        z-index: 0;
    }
    .balloon-left:after {
        content: '';
        position: absolute;
        width: 0px;
        height: 0px;
        border-top: 10px solid #aaa;
        border-left: 10px solid transparent;
        border-right: 10px solid transparent;
        -moz-transform: rotate(90deg);
        -webkit-transform: rotate(90deg);
        transform: rotate(90deg);
        top: 15px;
        left: -15px;
        z-index: -1;
    }
    .btn-pass{position:absolute;left: 100px;}
    .radio-inline2 input[type="radio"] {
        position: relative;
        top: -45px;
        left: -5px;
    }
    .radio-inline3 input[type="radio"] {
        position: relative;
        top: 27px;
        left: -30px;
    }
    .name {
        position: relative;
        left: 5px;
    }
    .result_img {
        width: 220px;
        margin: 0 auto;
        text-align: center;
    }
    .result_draw {
        width: 150px;
        margin: 0 auto 10px;
        text-align: center;
        background-color: rgba(255,255,255,0.9);
        padding: 5px;
        border: 1px solid #999;
        border-radius: 5px;
        -webkit-border-radius: 5px;
        -moz-border-radius: 5px;
    }
    table .cell > .disc {
        display: inline-block;
        max-width: 40px;
        max-height: 40px;
        width: calc((100vmin - (39px + 48px))/8);
        height: calc((100vmin - (39px + 48px))/8);
        border-radius: 50%;
        transform-style: preserve-3d;
        -webkit-transform-style: preserve-3d;
        -webkit-transform:perspective(0) rotateY(0deg);
        -moz-transform:perspective(0px) rotateY(0deg);
        transform:perspective(0px) rotateY(0deg);
        -webkit-transition:ease-out 0.1s -webkit-transform;
        -moz-transition:ease-out 0.1s -moz-transform;
        transition:ease-out 0.1s transform;
    }
    table {
        border-collapse: collapse;
        border-spacing: 0;
    }
    table .cell {
        line-height: 0  !important;
        background: #090;
        border: 1px solid #ccc;
        padding: 3px !important;
        margin: 0;
    }
    table .cell.black > .disc {
        background: #333;
    }
    table .cell.white > .disc {
        background: #fff;
    }
    table .cell.put {
        background: #290;
    }
    table .cell.put > .disc {
        background: #3A0;
    }
    @if ($turn == 0)
    #view {
        display: none;
    }
    @endif
    @if ($can_put)
    #pass {
        display: none;
    }
    @endif
    #end {
        display: none;
    }
</style>
@endpush

@push('js')
<script src="{{ url('js/jquery-3.2.1.min.js') }}"></script>
<script src="{{ url('js/jsdeferred.jquery.js') }}"></script>
<script type="text/javascript">

    var isPut = false;
    var isTurn = false;
    var turn = {{ $turn }};
    var level = {{ $level }};

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    function doSetTurn(){

        $('#select_turn').css('display','none');

        var dElm = document.documentElement , dBody = document.body;
        var nY = dElm.scrollTop || dBody.scrollTop;         //現在位置のY座標
        var cH = dElm.clientHeight || dBody.clientHeight;   //表示領域高
        nY = nY + cH / 2 - 20;
        if (nY > 320) nY = 320;
        $('#loading').css('margin-top',nY+'px');
        $('#modalContLoading').css('display','block');
        $('#loading').css('display','block');

        $.ajax({
            type: "POST",
            url: "/reversi/doSetTurn",
            data: {
                level : level,
                turn : turn
            },
            dataType: 'json',
            success: function(msg){
                $('#modalContLoading').css('display','none');
                $('#loading').css('display','none');

                $('#view').css('display','block');

                if (msg.error) {
                    window.location = 'ReversiSwf';
                }
                result = msg.result;
                level_list = msg.level_list;
                turn = msg.turn;
                change_turn = msg.change_turn;

                $('#enemy_img').html('<img class="img-small" src="/img/'+level_list[level].card_id+'.jpg">');
                $("#enemy_name").html(level_list[level].name);
                if (turn == 1) {
                    $("#enemy_turn").addClass("white");
                    $("#enemy_turn_name").html("後手");
                    $("#my_turn").addClass("black");
                    $("#my_turn_name").html("先手");
                } else if (turn == -1) {
                    $("#enemy_turn").addClass("black");
                    $("#enemy_turn_name").html("先手");
                    $("#my_turn").addClass("white");
                    $("#my_turn_name").html("後手");
                }

                next(function(){
                    if (change_turn) {
                        next(function(){
                            talk("俺が後手か？");
                            return wait(2);
                        }).
                        next(function(){
                            talk('だが断る');
                            return wait(2);
                        });
                        return wait(4);
                    }
                }).
                next(function(){
                    talk(result.talk);
                    return wait(2);
                }).
                next(function(){
                    if (result.opponent_put.length) {
                        return wait(1);
                    }
                }).
                next(function(){
                    if (result.opponent_put.length) {
                        if (turn == 1) {
                            $("#cell_"+result.opponent_put[0]+"_"+result.opponent_put[1]).addClass("white");
                        } else {
                            $("#cell_"+result.opponent_put[0]+"_"+result.opponent_put[1]).addClass("black");
                        }
                        return wait(0.5);
                    }
                }).
                next(function(){
                    if (result.opponent_tip_over.length) {
                        $.each(result.opponent_tip_over,function(){
                            if (turn == 1) {
                                discRotate("#cell_"+this[0]+"_"+this[1], "black", "white");
                            } else {
                                discRotate("#cell_"+this[0]+"_"+this[1], "white", "black");
                            }
                        });
                        return wait(1);
                    }
                }).
                next(function(){
                    if (result.next_put.length) {
                        $.each(result.next_put,function(){
                            $("#cell_"+this[0]+"_"+this[1]).addClass("put");
                        });
                        return wait(0.5);
                    }
                });
            }
        });
    }

    // talk用関数
    function talk(talk) {
        next(function(){
            $("#balloon").html(talk);
            $('#balloon').fadeIn(200);
            return wait(1.5);
        }).
        next(function(){
            $('#balloon').fadeOut(200);
            return wait(0.5);
        });
    }

    // カードめくり用関数
    function discRotate(cell, remove, add) {
        next(function(){
            $(cell).children("span").css("-webkit-transform" , "perspective(0) rotateY(-90deg)");
            $(cell).children("span").css("-moz-transform" , "perspective(0px) rotateY(-90deg)");
            $(cell).children("span").css("transform" , "perspective(0px) rotateY(-90deg)");
            return wait(0.2);
        }).
        next(function(){
            $(cell).removeClass(remove).addClass(add);
            return wait(0.1);
        }).
        next(function(){
            $(cell).children("span").css("-webkit-transform" , "perspective(0) rotateY(0deg)");
            $(cell).children("span").css("-moz-transform" , "perspective(0px) rotateY(0deg)");
            $(cell).children("span").css("transform" , "perspective(0px) rotateY(0deg)");
            return wait(0.2);
        });
    }

    $(document).ready(function() {

        Deferred.define();

        $('#start').bind('click',function() {
            if(!isTurn){
                isTurn = true;
                next(function(){
                    level = $('input[name=level]:checked').val();
                    turn = $('input[name=turn]:checked').val();
                    doSetTurn();
                });
            }
        });
    });

    $(function(){
        $('.table').on('click', '.put', function () {
            id = this.id;
            v = id.split("_");
            x = v[1];
            y = v[2];

            if(!isPut){
                isPut = true;

                var dElm = document.documentElement , dBody = document.body;
                var nY = dElm.scrollTop || dBody.scrollTop;         //現在位置のY座標
                var cH = dElm.clientHeight || dBody.clientHeight;   //表示領域高
                nY = nY + cH / 2 - 20;
                if (nY > 320) nY = 320;
                $('#loading').css('margin-top',nY+'px');
                $('#modalContLoading').css('display','block');
                $('#loading').css('display','block');

                $.ajax({
                    type: "POST",
                    url: "/reversi/doPut",
                    data: {
                        x : x,
                        y : y
                    },
                    dataType: 'json',
                    success: function(msg){
                        $('#modalContLoading').css('display','none');
                        $('#loading').css('display','none');
                        if (msg.error) {
                            window.location = 'ReversiSwf';
                        }
                        $(".put").removeClass("put");

                        result = msg.result;

                        next(function(){
                            if (result.my_put.length) {
                                if (turn == 1) {
                                    $("#cell_"+result.my_put[0]+"_"+result.my_put[1]).addClass("black");
                                } else {
                                    $("#cell_"+result.my_put[0]+"_"+result.my_put[1]).addClass("white");
                                }
                                return wait(0.5);
                            }
                        }).
                        next(function(){
                            if (result.my_tip_over.length) {
                                $.each(result.my_tip_over,function(){
                                    if (turn == 1) {
                                        discRotate("#cell_"+this[0]+"_"+this[1], "white", "black");
                                    } else {
                                        discRotate("#cell_"+this[0]+"_"+this[1], "black", "white");
                                    }
                                });
                                return wait(1);
                            }
                        }).
                        next(function(){
                            if (result.talk.length) {
                                talk(result.talk);
                                return wait(2);
                            }
                        }).
                        next(function(){
                            if (result.opponent_put.length) {
                                if (turn == 1) {
                                    $("#cell_"+result.opponent_put[0]+"_"+result.opponent_put[1]).addClass("white");
                                } else {
                                    $("#cell_"+result.opponent_put[0]+"_"+result.opponent_put[1]).addClass("black");
                                }
                                return wait(0.5);
                            }
                        }).
                        next(function(){
                            if (result.opponent_tip_over.length) {
                                $.each(result.opponent_tip_over,function(){
                                    if (turn == 1) {
                                        discRotate("#cell_"+this[0]+"_"+this[1], "black", "white");
                                    } else {
                                        discRotate("#cell_"+this[0]+"_"+this[1], "white", "black");
                                    }
                                });
                                return wait(1);
                            }
                        }).
                        next(function(){
                            if (msg.end) {
                                if (result.result == 1) {
                                    $('#result').html('<div class="result_draw">勝利</div>');
                                } else if (result.result == -1) {
                                    $('#result').html('<div class="result_draw">敗北</div>');
                                } else {
                                    $("#result").html('<div class="result_draw">引き分け</div>');
                                }
                                $('#end').fadeIn(1000);
                            } else if (result.next_put.length) {
                                $.each(result.next_put,function(){
                                    $("#cell_"+this[0]+"_"+this[1]).addClass("put");
                                });
                            } else {
                                $('#pass').css('display','block');
                            }
                            isPut = false;
                            return wait(0.5);
                        });
                    },
                    complete: function(msg){
                    }
                });
            }
        });
    });

    $(function(){
        $('#animelim').on('click', '#pass', function () {

            if(!isPut){
                isPut = true;

                var dElm = document.documentElement , dBody = document.body;
                var nY = dElm.scrollTop || dBody.scrollTop;         //現在位置のY座標
                var cH = dElm.clientHeight || dBody.clientHeight;   //表示領域高
                nY = nY + cH / 2 - 20;
                if (nY > 320) nY = 320;
                $('#loading').css('margin-top',nY+'px');
                $('#modalContLoading').css('display','block');
                $('#loading').css('display','block');

                $('#pass').css('display','none');

                $.ajax({
                    type: "POST",
                    url: "/reversi/doPass",
                    data: {},
                    dataType: 'json',
                    success: function(msg){
                        $('#modalContLoading').css('display','none');
                        $('#loading').css('display','none');
                        if (msg.error) {
                            window.location = 'ReversiSwf';
                        }

                        result = msg.result;
                        if (msg.end) {
                            if (result.result == 1) {
                                $('#result').html('<div class="result_draw">勝利</div>');
                            } else if (result.result == -1) {
                                $('#result').html('<div class="result_draw">敗北</div>');
                            } else {
                                $("#result").html('<div class="result_draw">引き分け</div>');
                            }
                            $('#end').fadeIn(1000);
                            return;
                        }
                        $(".put").removeClass("put");

                        next(function(){
                            return wait(1);
                        }).
                        next(function(){
                            if (result.opponent_put.length) {
                                if (turn == 1) {
                                    $("#cell_"+result.opponent_put[0]+"_"+result.opponent_put[1]).addClass("white");
                                } else {
                                    $("#cell_"+result.opponent_put[0]+"_"+result.opponent_put[1]).addClass("black");
                                }
                                return wait(0.5);
                            }
                        }).
                        next(function(){
                            if (result.opponent_tip_over.length) {
                                $.each(result.opponent_tip_over,function(){
                                    if (turn == 1) {
                                        discRotate("#cell_"+this[0]+"_"+this[1], "black", "white");
                                    } else {
                                        discRotate("#cell_"+this[0]+"_"+this[1], "white", "black");
                                    }
                                });
                                return wait(1);
                            }
                        }).
                        next(function(){
                            if (result.next_put.length) {
                                $.each(result.next_put,function(){
                                    $("#cell_"+this[0]+"_"+this[1]).addClass("put");
                                });
                            } else {
                                $('#pass').css('display','block');
                            }
                            isPut = false;
                        });
                    },
                    complete: function(msg){
                    }
                });
            }
        });
    });

    $(function(){
        $('#nextbutton').click(function(e){
            window.location = "{{ url($return_url) }}";
        });
    });
</script>
@endpush