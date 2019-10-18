/* function refresh_db () {
   var result = "<?php refreshdb();?>";
   alert(result);
   //return false;
} */

$(document).ready(function(){
   $("#refresh").click(function(){
      $(this).fadeTo(1000,.5,function(){
         $(this).fadeTo(1000,1)});
   });
});

$(document).ready(function(){
   $("#refresh").click(function(){
      $.post("../php/moviequery.php");
   });
});

function getMovies(sTitle){
   
}