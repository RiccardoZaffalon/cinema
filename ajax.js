//TMDB API search function
function search(title) {
    $.ajax({
        type: 'GET',
        url: 'https://api.themoviedb.org/3/search/movie',
        data: { query: title },
        beforeSend: function() {
            $('#search-results').html('<li><img src="loader_dark.gif" alt=""></li>');
        },
        success: function(data) {
            if (data.results[0] != null) {
                //Movie found
                console.log(data);
                template = Handlebars.compile(source_1);
                html = template({ results: data.results });
                $('#search-results').html(html);
            } else {
                //Movie not found
                $('#search-results').html('<li>No movie found.</li>');
            }
        },
        error: function() {
            console.log('Error.');
        }
    });
}

//Database API functions
//Get movies, render list and update graphs
function updateMovies(order) { 
    $.ajax({
        type: 'GET',
        data: { order: order },
        url: 'api.php',
        dataType: 'JSON',
        success: function(data) {
            console.log('Movie list updated.');
            template = Handlebars.compile(source_2);
            html = template({ movies: data.results });
            $('#saved-movies').html(html);
            //Update ChartJS
            var statistics = organizeData(data.genres_frequencies);
            genresChart.data.datasets[0].data = statistics.values;
            genresChart.data.labels = statistics.keys;
            genresChart.update();

            var decades = organizeData(data.decades_frequencies);
            decadesChart.data.datasets[0].data = decades.values;
            decadesChart.data.labels = decades.keys;
            decadesChart.update();

            var watched = data.stats.watched_movies;
            var total = data.stats.total_movies;
            watchedChart.data.datasets[0].data = [watched, total - watched];
            watchedChart.update();
        }
    })
}

//Get movies, update graphs only
function updateGraphs(order) {
    $.ajax({
        type: 'GET',
        data: { order: order },
        url: 'api.php',
        dataType: 'JSON',
        success: function(data) {
            console.log('Graphs updated.');
            //Update ChartJS
            var statistics = organizeData(data.genres_frequencies);
            genresChart.data.datasets[0].data = statistics.values;
            genresChart.data.labels = statistics.keys;
            genresChart.update();

            var decades = organizeData(data.decades_frequencies);
            decadesChart.data.datasets[0].data = decades.values;
            decadesChart.data.labels = decades.keys;
            decadesChart.update();

            var watched = data.stats.watched_movies;
            var total = data.stats.total_movies;
            watchedChart.data.datasets[0].data = [watched, total - watched];
            watchedChart.update();
        }
    })
}

//Add movie to DB
function addMovie(id, title, overview, date, poster, vote, genres) { 
    $.ajax({
        type: 'POST',
        url: 'api.php',
        data: {
            movie_id: id,
            movie_title: title,
            movie_overview: overview,
            movie_date: date,
            movie_poster: poster,
            movie_vote: vote,
            movie_genres: genres
        },
        success: function(data) {
            console.log(data);
            if (data == 'true') {
                toastr["success"]("Movie added.");
                updateMovies();
            } else {
                toastr["warning"]("Movie alreay saved.");
            }
            
        }
    })
}

//Remove movie from DB
function removeMovie(id) { 
    $.ajax({
        type: 'DELETE',
        url: 'api.php',
        data: { id: id },
        success: function(data) {
            toastr["success"]("Movie removed.");
            console.log(data);
            updateGraphs();
        }
    })
}

//Update movie love in DB
function updateLove(id, love) { 
    $.ajax({
        type: 'PUT',
        url: 'api.php',
        data: { id: id, movie_love: love },
        success: function(data) {
            console.log(data);
        }
    })
}

//Update movie watched status in DB
function updateWatched(id, watched) { 
    $.ajax({
        type: 'PUT',
        url: 'api.php',
        data: { id: id, movie_watched: watched },
        success: function(data) {
            console.log(data);
            updateGraphs();
        }
    })
}

//Update sorting method in settings
function sortMovies(order) {
    $.ajax({
        type: 'PUT',
        url: 'settings.php',
        data: { sort: order },
        success: function(data) {
            updateMovies();
        }
    })
}
