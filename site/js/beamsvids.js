// Beams v0.1.2 (Beta)
// Copyright (c) 2014-2015 Peter McKay
// Free to use under the MIT license.

$(document).ready(function(){
  var rvw1 =  $(".video").width(); 
   $(".video").height(rvw1 *= 0.5625);    //Sets a 16:9 aspect ratio for third-party video players tagged in CSS with class "video."
});

$(window).resize(function() {
  var rvw2 =  $(".video").width(); 
  $(".video").height(rvw2 *= 0.5625);   //Maintains 16:9 aspect if viewport changes size.
});
