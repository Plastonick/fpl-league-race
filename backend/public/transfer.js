window.addEventListener('load', () => {
  document.getElementById('submit-user').onclick = submitUser
})

function submitUser () {
  const http = axios
  const userId = Number(document.getElementById('user-id').value)
  const draft = Number(document.getElementById('is-draft').checked) ? '/draft' : ''

  http.get(`/transfers/${userId}` + draft)
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
