function loadMoreTricks()
{
   let $tricksDiv = $('#tricks');
   let offset = $('.trick-card').length;
   let requestUrl = "/tricks/load/";
   let max = 15;

   requestUrl += offset + "/" + max;

   $.ajax({
      url: requestUrl,
      type: "POST",

      success: function (response) {
         if(response.tricksCount < max) {
            $('#load-more-btn').css('display', 'none');
         }
         $tricksDiv.append(response.template);
      },

      error: function() {
         alert("Un problème est survenu lors de l'opération.");
      }
   });
}
