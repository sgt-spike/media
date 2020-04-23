<!DOCTYPE html>
<html lang="en">
<head>
<title>Chriss Movie Library</title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<link rel="stylesheet" href="css/main.css" type="text/css">
</head>

<body>
	<div class="body-grid">
		<div class="header header--grid">
			<div class="header__title">
				<h1><a class="aLink" href="index.php">The Chriss Movie Collection</a></h1>
			</div>
			<div class="header__search">
				<div class="search--grid">
					<form class="form form-grid" method="get" id="search" action="index.php">
								  <input class="form__input" type="text" name="search" placeholder="Search for Movies" id="searchField">
					</form>
					<form id="refresh">
						<button class="btn btn--med btn--blue" type="submit" form="search">Movie Search</button>
						<button class="btn btn--med btn--blue" type="submit" formaction="index.php" form="search">Clear Search</button>
						<button class="btn btn--med btn--blue" type="submit" value="" formaction="refresh_db.php?refresh=" form="refresh">Refresh Library</button>
					</form>
				</div>
			</div>
		</div>
	<main id="main" class="content--grid">