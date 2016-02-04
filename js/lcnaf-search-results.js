(function ($) {
  Drupal.behaviors.lcnafSearchResults = {
    attach: function (context, settings) {
      
      $(context).find('.lcnaf-table-results .lcnaf-result-link-add-authority').once('lcnafSearchResults').each(function() {
        // Apply the lcnafSearchResults effect to #lcnaf-search-results elements only once.
        $(this).click(function(e) {
          
          // pass the LLCN to the submit handler in a hidden field
          //var lccn = $(this).data("lcnaf-lccn");
          var lccn = $(this).parents("tr").find(".lcnaf-result-lccn input").val();
          $("form#lcnaf-ajax-request .lcnaf-lccn-value").val(lccn);
          
          // submit the form
          
          //console.log("Add Authority Name - button triggered a form submit. LLCN = " + lccn);
          
          //$("form#lcnaf-ajax-request").submit(function(f) {
            //console.log("Submit handler triggered");
            //f.preventDefault();
          //});
          
          //$("form#lcnaf-ajax-request").submit();
          //$(this).closest("form").submit();
          
          // disable link behavior
          //e.preventDefault();
          //return false;
          
          return true;
        });
      });
      
    }
  };
})(jQuery);
