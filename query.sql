-- Query movies2 tbl by alpha character
select `moviesdb`.`movies2`.`movie_id` AS `movie_id`,`moviesdb`.`movies2`.`new_id` AS `new_id`,`moviesdb`.`movies2`.`title` AS `title`,`moviesdb`.`movies2`.`artists` AS `artists`,`moviesdb`.`movies2`.`genre` AS `genre`,`moviesdb`.`movies2`.`description` AS `description`,`moviesdb`.`movies2`.`description_long` AS `description_long`,`moviesdb`.`movies2`.`creation_date` AS `creation_date`,`moviesdb`.`movies2`.`cover` AS `cover`,`moviesdb`.`movies2`.`media` AS `media` from `moviesdb`.`movies2` where `moviesdb`.`movies2`.`title` regexp '^[Kk]' order by `moviesdb`.`movies2`.`title`,`moviesdb`.`movies2`.`creation_date`;



-- Insert movies to movies tbl from movies2 tbl
INSERT IGNORE INTO movies (title, description, description_long, creation_date)
SELECT title, description, description_long, creation_date FROM Movies2J

-- Update movies2.new_id with movies.movie_id
update movies2
   set new_id = (SELECT movie_id
                   FROM movies
                  WHERE title regexp '^[Kk]'
                    AND title = movies2.title 
                    AND creation_date = movies2.creation_date);

SELECT x.movie_id, x.title, y.new_id, y.title FROM movies x, Movies2H y WHERE x.movie_id = y.new_id

-- Insert movie_id and genre_id into genre_movie
INSERT IGNORE INTO genre_movie (movie_id, genre_id)
SELECT * FROM `movie_id-genre_id->genre_movie`;



-- Insert movie_id and media_id into movie_media tbl
INSERT IGNORE INTO movie_media (movie_id, media_id)
SELECT * FROM `movie_id-medie_id->movie_media`;



-- Insert artists into the artist_name tbl from movies2 tbl
INSERT IGNORE INTO artist_names (artist_name)
SELECT artist FROM `movie_artist_tbl_move`;

SELECT COUNT(DISTINCT(movie_id)) AS 'Movie Count in genre_movie' FROM genre_movie; 

SELECT COUNT(DISTINCT(movie_id)) AS 'Movies Count in movie_media' FROM movie_media;

SELECT COUNT(*) AS 'Total Movie Count' FROM movies;

