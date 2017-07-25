{{--
    開始
    var dElm = document.documentElement , dBody = document.body;
    var nY = dElm.scrollTop || dBody.scrollTop;     //現在位置のY座標
    var cH = dElm.clientHeight || dBody.clientHeight;   //表示領域高
    nY = nY + cH / 2 - 20;
    $('#loading').css('margin-top',nY+'px');
    $('#modalContLoading').css('display','block');
    $('#loading').css('display','block');

    終了
    success: function(msg){
    ・・・
    },
    complete: function(msg){
        $('#modalContLoading').css('display','none');
        $('#loading').css('display','none');
    }
--}}
@push('css')
<style type="text/css">
    #loading {
        z-index:100;
        position: absolute;
        width: 30px;
        height: 30px;
        left:50%;
        top: 0;
        margin:0 0 0 -12px;
        background:url(/img/loading.gif) no-repeat;
        display:none;
    }
</style>
@endpush

@push('view')
<div id="modalContLoading" style="z-index:100;">&nbsp;</div>
<div id="loading">
&nbsp;
</div>
@endpush