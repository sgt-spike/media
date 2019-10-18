document.getElementById('searchform').addEventListener('submit', getMovies);

function getMovies(e){
   e.preventDefault();

   document.querySelector('#main-all').style.display = 'none';
   
   createContainer();

   let str = document.getElementById('searchField').value;


   
   $params = `search=${str}`; 
   let xhr = new XMLHttpRequest();
   document.querySelector('#searchField').value = "";
   
   xhr.open('POST', '../php/moviequery.php', true);
   xhr.setRequestHeader('content-type', 'application/x-www-form-urlencoded');

   xhr.onload = function(){
      if(this.status == 200){
         let movies = JSON.parse(this.responseText);

         let output = '';

         for(let movie in movies) {
            output += `<li class="flex-item flex-wrap">
                           <div id="item-title">${movies[movie].title}</div>
                           <div id="item-media">${movies[movie].media}</div>
                        </li>`;
         }
         document.querySelector('#movieList').innerHTML = output;
      }
   }
   xhr.send($params);
}

function createContainer() {
   let main = document.querySelector('#main');
   mainSelect = document.createElement('div');
   mainSelect.id = 'main-select';
   mainSelect.className = 'container';
   ul = document.createElement('ul');
   ul.className = 'flex-container';
   ul.id = 'movieList'
   mainSelect.appendChild(ul);
   main.appendChild(mainSelect);
   mainSelect.style.display = 'initial';
}
