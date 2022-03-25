var currentBodyHeight = $('body').height()
var htmlLoading = "<div id='loaderSaveRequest' style='position:fixed;top:0;left:0;right:0;bottom:0;background-color:rgba(240, 243, 249, 0.5);z-index:100;height:"+currentBodyHeight+";'>"
    htmlLoading += "<div style='top:50%;left:50%; position:fixed; transform: translate(-50%, -50%); vertical-align:middle;'><div class='sk-fading-circle'>"
var skCircle = ""
    for (var i = 0; i <= 12; i++) {
        skCircle += "<div class='sk-circle"+i+" sk-circle'></div>"
    }
    htmlLoading += skCircle
    htmlLoading += "</div>"
    htmlLoading += "<div style='padding-top:10px; width:100%; color:#67a4eb; text-align:center;'><b class='text-loading-processing'>Processing...</b></div>"
    htmlLoading += "</div></div>"


function showProgress(tlp = 'Processing...') {
    $('button').prop('disabled', true);
    $('.app-body').css('filter', 'blur(3px)')
    $('body').append(htmlLoading)
    $('.text-loading-processing').text(tlp)
    $('.sk-fading-circle').css('margin', '6px auto')
}

function hideProgress(){
    $('button').prop('disabled', false);
    $('.app-body').css('filter', 'none')
    $('#loaderSaveRequest').remove()
}

