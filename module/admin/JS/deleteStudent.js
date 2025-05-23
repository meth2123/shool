function getStudentForDelete(str) {
    if (str.length === 0) {
        document.getElementById("deleteStudentData").innerHTML = '<tr><td colspan="12" class="px-6 py-4 text-center text-gray-500">Commencez à taper pour rechercher un étudiant...</td></tr>';
        return;
    }

    const xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
        if (this.readyState == 4) {
            const container = document.getElementById("deleteStudentData");
            if (this.status == 200) {
                container.innerHTML = this.responseText;
                // Réinitialiser la case à cocher "Tout sélectionner"
                document.getElementById("selectAll").checked = false;
            } else {
                container.innerHTML = '<tr><td colspan="12" class="px-6 py-4 text-center text-red-500">Erreur lors de la recherche</td></tr>';
            }
        }
    };
    xhttp.open("GET", "searchForDeleteStudent.php?key=" + encodeURIComponent(str), true);
    xhttp.send();
} 