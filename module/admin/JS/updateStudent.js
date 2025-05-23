function getStudentForUpdate(str) {
    if (str.length === 0) {
        document.getElementById("updateStudentData").innerHTML = "";
        return;
    }

    const xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
        if (this.readyState == 4) {
            const container = document.getElementById("updateStudentData");
            if (this.status == 200) {
                container.innerHTML = this.responseText;
            } else {
                container.innerHTML = '<tr><td colspan="13" class="px-6 py-4 text-center text-red-500">Erreur lors de la recherche</td></tr>';
            }
        }
    };
    xhttp.open("GET", "searchForUpdateStudent.php?key=" + encodeURIComponent(str), true);
    xhttp.send();
} 