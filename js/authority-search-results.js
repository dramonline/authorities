(function ($) {
  Drupal.behaviors.authoritySearchResults = {
    attach: function (context, settings) {
      
      // Apply the authoritySearchResults effect to Add Authority button elements only once.
      $(context).find('.table-search-results .result-link-add-authority').once('authoritySearchResults').each(function() {
        $(this).click(function(e) {

          // pass the authority id (LCCN, etc.) to the submit handler in a hidden field
          var authority_id = $(this).parents("tr").find(".authority-id input").val();
          $("form#authority-search-form .authority-id-value").val(authority_id);
          
          return true;
        });
      });
      
    }
  };
})(jQuery);
