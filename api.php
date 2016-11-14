<?php
require_once('app/config.php');

//Detect API request method
switch ($_SERVER['REQUEST_METHOD']) {
	
	//Add new movie
	case 'POST':
		$movie_id = $_POST['movie_id'];
		$movie_title = $_POST['movie_title'];
		$movie_overview = truncate($_POST['movie_overview'], 20);
		$movie_date = $_POST['movie_date'];
		$movie_poster = $_POST['movie_poster'];
		$movie_vote = $_POST['movie_vote'];
		$movie_genres = $_POST['movie_genres'];
		//Check if movie is already in DB
		$stmt = $db->prepare("SELECT * FROM movies WHERE movie_id=?");
		$stmt->execute(array($movie_id));
		$row_count = $stmt->rowCount();
		if ($row_count == 0) {
		    //Add movie to DB if not present
		    $stmt_add = $db->prepare("INSERT INTO movies(movie_id, movie_title, movie_overview, movie_date, movie_poster, movie_vote, movie_genres) VALUES(?, ?, ?, ?, ?, ?, ?)");
		    $stmt_add->execute(array($movie_id, $movie_title, $movie_overview, $movie_date, $movie_poster, $movie_vote, $movie_genres));
		    $insertId = $db->lastInsertId();
		    echo ('true');
		} else {
		    echo ('false');
		}
		break;

	//Read movie list and data
	case 'GET':
		$order_query=$db->prepare("SELECT sort FROM settings"); //Get sorting order from settings
		$order_query->execute();
		while($row = $order_query->fetch(PDO::FETCH_ASSOC)) {
		    $order = $row['sort'];
		}
		switch ($order) { //Return different order based on setting
			case 'magic':
				$query = "SELECT * FROM movies ORDER BY movie_love DESC, movie_date DESC, movie_title";
				break;
			case 'title':
				$query = "SELECT * FROM movies ORDER BY movie_title";
				break;
			case 'date-asc':
				$query = "SELECT * FROM movies ORDER BY movie_date ASC, movie_title";
				break;
			case 'date-desc':
				$query = "SELECT * FROM movies ORDER BY movie_date DESC, movie_title";
				break;
			case 'love':
				$query = "SELECT * FROM movies ORDER BY movie_love DESC, movie_title";
				break;
			default:
				$query = "SELECT * FROM movies ORDER BY ID DESC";
				break;
		}
		$stmt=$db->prepare($query);
		$stmt->execute();
		$results=$stmt->fetchAll(PDO::FETCH_ASSOC);	
		//Calculate movie decades
		foreach ($results as $result) {
		    $dates_results[] = getDecade($result['movie_date']);
		}
		asort($dates_results);
		$dates_freq = array_count_values($dates_results);
		//Search movies marked as watched
		$watched=$db->prepare("SELECT movie_watched FROM movies WHERE movie_watched=1");
		$watched->execute();
		//Movie genres results		
		$genres=$db->prepare("SELECT genre_name FROM movies a INNER JOIN genres b ON find_in_set(b.genre_id, a.movie_genres)>0");
		$genres->execute();
		$genres_results = [];
		while($row = $genres->fetch(PDO::FETCH_ASSOC)) {
		    $genres_results[] = $row['genre_name'];
		}
		$genre_freq = (array_count_values($genres_results));
		//Combine Statistics and Results into single object to send back
		$stats = array('total_movies'=> $stmt->rowCount(), 'watched_movies' => $watched->rowCount());
		//Serve JSON with combined data
		header('Content-Type: application/json');
		print_r(json_encode(array_merge(
			array('stats'=>$stats),
			array('results'=>$results),			
			array('decades_frequencies'=>$dates_freq),
			array('genres_frequencies'=>$genre_freq)
		)));
		break;
	
	//Delete selected movie given database ID
	case 'DELETE':
		parse_str(file_get_contents("php://input"),$post_vars);
		$id = $post_vars['id'];
		$stmt = $db->prepare("DELETE FROM movies WHERE id=:id");
		$stmt->bindValue(':id', $id, PDO::PARAM_STR);
		$stmt->execute();
		break;
	
	//Update movie data
	case 'PUT':
		parse_str(file_get_contents("php://input"),$post_vars);
		$id = $post_vars['id'];
		//If update movie love
		if (isset($post_vars['movie_love'])) {			
			$movie_love = $post_vars['movie_love'];
			$stmt = $db->prepare("UPDATE movies SET movie_love=:love WHERE id=:id");
			$stmt->bindValue(':id', $id, PDO::PARAM_STR);
			$stmt->bindValue(':love', $movie_love, PDO::PARAM_INT);
		}
		//If update movie watched
		if (isset($post_vars['movie_watched'])) {			
			$movie_watched = $post_vars['movie_watched'];
			$stmt = $db->prepare("UPDATE movies SET movie_watched=:watched WHERE id=:id");
			$stmt->bindValue(':id', $id, PDO::PARAM_STR);
			$stmt->bindValue(':watched', $movie_watched, PDO::PARAM_INT);
		}
		$stmt->execute();
		break;

	//Other methods
	default:
		echo 'Method not available.';
}

//Close connection
$db = null;
