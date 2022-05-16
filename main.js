const http = axios;

http.get('http://localhost:9098/race-data.php?leagueId=123')
    .then(function (response) {
        // handle success
        console.log(response);
    })
    .catch(function (error) {
        // handle error
        console.error(error);
    })
    .then(function () {
        // always executed
    });
