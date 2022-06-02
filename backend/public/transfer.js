window.addEventListener('load', () => {
  document.getElementById('submit-user').onclick = submitUser
})

function submitUser () {
  const http = axios
  const leagueId = Number(document.getElementById('user-id').value)

  http.get(`/transfers/${leagueId}`)
    .then(function (response) {
      document.getElementById('bar-chart-race').innerHTML = response.data
    })
    .catch(function (error) {
      // handle error
      console.error(error)
    })
    .then(function () {
      // always executed
    })
}
