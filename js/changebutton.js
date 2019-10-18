function changebutton() {
   document.getElementById('extra').value = document.getElementById("searchtext").value
   var querysearch = document.getElementById("extra").value
   if  (querysearch) {
      document.getElementById('searchbtn').innerHTML = "Show All";
   }
   else if (!querysearch) {
      alert("Nothing is set")
      document.getElementById('searchbtn').innerHTML = "Search";
   }
}
