function recalculatePageHeight () {
    var height = jQuery(document).height();
    $(".sidebar").css("height",height);
}

function simpleAdvert (title,content) {
    $.confirm({
        title: title,
        content: content,

        boxWidth: '90%',
        useBootstrap: false,
    });
}

function simpleAdvertError (title,content) {
    $.confirm({
        title: title,
        content:content,
    
    
        boxWidth: '90%',
        useBootstrap: false,
        type: 'red',
        typeAnimated: true,
    
        buttons: {
            tryAgain: {
                text: 'Ok',
                btnClass: 'btn-red',
                action: function(){
                }
            },
        }
    });
}

function simpleAdvertSuccess (title,content) {
    $.confirm({
        title: title,
        content:content,
    
    
        boxWidth: '90%',
        useBootstrap: false,
        type: 'green',
        typeAnimated: true,
    
        buttons: {
            tryAgain: {
                text: 'Ok',
                btnClass: 'btn-green',
                action: function(){
                }
            },
        }
    });
}

