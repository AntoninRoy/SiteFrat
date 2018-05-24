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