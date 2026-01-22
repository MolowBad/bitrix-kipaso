(document.addEventListener('DOMContentLoaded', function() {
  const links = [
    "https://max.ru/u/f9LHodD0cOLWaqXbsfhtAJaBO6cwRUkG4AJZvF_avpA9OKZOFgvqJWaF6SI",
    "https://max.ru/u/f9LHodD0cOLSi_77CX-jWjIngV0GJYFD2WZhshl88G_sBkm_qPO4cz8YXm0",
    "https://max.ru/u/f9LHodD0cOItmseGnnNqJdLhz6mreH_6bOGZKCRaB2Qhz8L_i6kEkItmAbc",
    "https://max.ru/u/f9LHodD0cOJWxPgzpZxxfALGxfZxvfAaGpw5ILG3nvstnkJs33l_1LSCoj4",
    "https://max.ru/u/f9LHodD0cOLT2sBi22yJN-VdRZaB4AjUgAHetcN7avGYCRrmY7cnn8HJsd4"
  ];

  const intervalMinutes = 15;
  const now = new Date();
  const minutesSinceMidnight = now.getHours() * 60 + now.getMinutes();
  const index = Math.floor(minutesSinceMidnight / intervalMinutes) % links.length;

  const linkEl = document.querySelector('a.max-link'); // ← изменён селектор
  if (linkEl) {
    linkEl.href = links[index];
  }
})());