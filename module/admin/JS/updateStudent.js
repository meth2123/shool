function getStudentForUpdate(str) {
    const container = document.getElementById("updateStudentData");
    
    if (str.length === 0) {
        container.innerHTML = `
            <tr>
                <td colspan="13" class="text-center text-muted">
                    <div class="py-4">
                        <i class="fas fa-search fa-2x mb-3"></i>
                        <p>Commencez à taper pour rechercher un étudiant...</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    // Afficher un indicateur de chargement
    container.innerHTML = `
        <tr>
            <td colspan="13" class="text-center">
                <div class="py-4">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p>Recherche en cours...</p>
                </div>
            </td>
        </tr>
    `;

    const xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
        if (this.readyState == 4) {
            if (this.status == 200) {
                container.innerHTML = this.responseText;
            } else {
                container.innerHTML = `
                    <tr>
                        <td colspan="13" class="text-center">
                            <div class="py-4 text-danger">
                                <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                                <p>Erreur lors de la recherche. Veuillez réessayer.</p>
                            </div>
                        </td>
                    </tr>
                `;
            }
        }
    };
    xhttp.open("GET", "searchForUpdateStudent.php?key=" + encodeURIComponent(str), true);
    xhttp.send();
}