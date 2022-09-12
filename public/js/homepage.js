function loadMoreTricks()
{
   let $tricksDiv = $('#tricks');
   let currentCount = $('#tricks > div').length;
   let offset = $('.trick-card').length;
   let requestUrl = "/tricks/load/";
   let max = 15;

   requestUrl += offset + "/" + max;

   $.ajax({
      url: requestUrl,
      type: "POST",

      success: function (response) {
         $tricksDiv.append(response.template);
         if($('#tricks > div').length - currentCount < max) {
            $('#load-more-btn').css('display', 'none');
         }
      },

      error: function() {
         alert("Un problème est survenu lors de l'opération.");
      }
   });
}
