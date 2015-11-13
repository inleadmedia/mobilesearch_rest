'use strict';

(function($) {
  
  $(document).ready(function() {
    $('.operation h3').click(function() {
      $(this).next('.operation-methods').toggle();
    });
    
    $('.operation-method .heading').click(function() {
      $(this).next('.method-doc').toggle();
    });
  });
  
})(jQuery);
