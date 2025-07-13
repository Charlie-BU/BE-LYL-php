function showErrorMsg(msg,callback=function () {}) {
    var tg = TGTool();
    tg.error(msg,callback);
}
function showSuccessMsg(msg,callback=function () {}) {
    var tg = TGTool();
    tg.success(msg,callback);
}
function showConfirm(msg,callback=function () {},title='提示',type=''){
    $.confirm({
        title: title,
        content: msg,
        type: type,//red green orange
        typeAnimated: true,
        buttons: {
            tryAgain: {
                text: '确定',
                btnClass: 'btn-red',
                action: function(){
                    callback();
                }
            },
            close: {
                text: '取消'
            }
        }
    });
}
$(function () {
    var config = {
        // How long Waves effect duration
        // when it's clicked (in milliseconds)
        duration: 500,
        // Delay showing Waves effect on touch
        // and hide the effect if user scrolls
        // (0 to disable delay) (in milliseconds)
        delay: 200
    };
    Waves.init(config);
    Waves.attach('.submit-btn');
    //开关选中
    $('.form-group-toggle .radio-btn').on('click',function () {
        if($(this).hasClass('active'))return;
        $(this).addClass('active').siblings().removeClass('active');
        $(this).find('input').prop('checked',true);
    });
});
/*
 * 上传图片 后台专用
 * @access  public
 * @null int 一次上传图片张图
 * @elementid string 上传成功后返回路径插入指定ID元素内
 * @path  string 指定上传保存文件夹,默认存在public/upload/temp/目录
 * @callback string  回调函数(单张图片返回保存路径字符串，多张则为路径数组 )
 */
function GetUploadify(num,elementid,path,callback,fileType='Images')
{
    var upurl ="/index.php/houtai/uploadify?num="+num+"&input="+elementid+"&path="+path+"&func="+callback+"&fileType="+fileType;
    var title = '上传图片';
    if(fileType == 'Flash'){
        title = '上传视频';
    }else if(fileType=='imageVideo'){
        title = '上传图片/视频';
    }
    layer.open({
        type: 2,
        title: title,
        shadeClose: true,
        shade: false,
        maxmin: true, //开启最大化最小化按钮
        area: ['50%', '60%'],
        content: upurl
    });
}
//公共表单提交
function public_ajax(url,data,callback=function () {}) {
    $.ajax({
        type: "POST",
        url: url,
        data: data,
        dataType: 'json',
        success: function (data) {
            callback(data);
            return;
            if (data.code == 1) {
                showSuccessMsg(data.msg, function () {
                    if (data.result.url) {
                        location.href = data.result.url;
                    }
                });
            } else if (data.code == 10) {
                showErrorMsg(data.msg);
                $.each(data.result, function(index, item) {
                    $('#err_' + index).text(item).show();
                    $('#err_' + index).parent().addClass('has-error');
                });
            } else {
                showErrorMsg(data.msg);
            }
        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            showErrorMsg("网络失败，请刷新后重试!");
        }
    });
}
//修改某个字段的值
function changeTableVal(table,field,ids,value,callback=function () {}) {
    var url="/houtai/tableVal?table="+table+"&field="+field+"&ids="+ids+"&value="+value;
    public_ajax(url,'',function (res) {
        callback(res)
    })
}