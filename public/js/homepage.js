function loadMoreTricks()
{
   let $tricksDiv = $('#tricks');
   let preLoadCount = $('#tricks > div').length;
   let offset = $('.trick-card').length;
   let requestUrl = "/tricks/load/";
   let max = 15;

   requestUrl += offset + "/" + max;

   $.ajax({
      url: requestUrl,
      type: "POST",

      success: function (response) {
         $tricksDiv.append(response.template);

         let newCount = $('#tricks > div').length;
         if(newCount > max) {
            $('#navTricksBtn').css("display", "inline-block");
         }

         if(newCount - preLoadCount < max) {
            $('#load-more-btn').css('display', 'none');
         }
      },

      error: function() {
         alert("Un problème est survenu lors de l'opération.");
      }
   });
}
