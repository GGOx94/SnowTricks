let formContainer = null;

function openForm(ajaxUrl, formType)
{
    if(formContainer !== null && formContainer.css("display") === "block")
        closeForm();

    formContainer = $($('.popup-form-container.' + formType)[0]);

    // We need to reset data-url so JQuery won't use the cached version in ajax requests
    formContainer.removeData('app-url');
    formContainer.data('app-url', ajaxUrl);

    formContainer.css("display", "block");
}

function postForm()
{
    initToast();
    $.ajax({
        url: formContainer.data('app-url'),
        type: "POST",
        data: new FormData(formContainer.find('form')[0]),
        contentType:false, processData:false, cache:false,

        success: function (response) {
            toastr.success(response.message);
            $("#carousel-template").html(response.template);
            closeForm();
            refreshCarouselControls();
        },

        error: function(err) {
            console.log(err);
            toastr.error(err.responseJSON.message);
            closeForm();
        }
    });
}

function closeForm()
{
    formContainer.css("display", "none");
}

function deleteMedia(ajaxUrl)
{
    initToast();
    $.ajax({
        url: ajaxUrl,
        type: "POST",

        success: function (response) {
            $("#carousel-template").html(response.template);
            toastr.success(response.message);
        },

        error: function() {
            toastr.error("Un problème est survenu lors de l'opération.");
        }
    });
}

function refreshCarouselControls()
{
    let mediaCrsl = document.querySelector('.trick-media-carousel');
    let prv = document.querySelector('#btn-prev');
    let nxt = document.querySelector('#btn-next');
    let media = document.querySelector('.media');

    if(prv != null && nxt != null)
    {
        nxt.addEventListener('click', function() { mediaCrsl.scrollLeft += media.clientWidth + 2; });
        prv.addEventListener('click', function() { mediaCrsl.scrollLeft -= media.clientWidth + 2; });
    }
}

$(document).ready(function()
{
    refreshCarouselControls();
});
