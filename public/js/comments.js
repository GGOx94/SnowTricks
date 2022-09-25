function loadMoreComments(slug)
{
    let currentCount = $('#trick-comments > div').length;
    let offset = $('.comment').length;
    let requestUrl = "/trick/" + slug + "/comments/load/";
    let max = 10;

    requestUrl += offset + "/" + max;

    $.ajax({
        url: requestUrl,
        type: "POST",

        success: function (response) {
            $('#trick-comments').append(response.template);
            if($('#trick-comments > div').length - currentCount < max) {
                $('#load-more-btn').css('display', 'none');
            }
        },

        error: function() {
            alert("Un problème est survenu lors de l'opération.");
        }
    });
}
