document.querySelector('#cancelBtn').addEventListener('click', function(){
   let main = document.querySelector('#main')
   let mainSelect = main.children[1];
   main.removeChild(mainSelect);
   document.querySelector('#main-all').style.display = 'initial';
})