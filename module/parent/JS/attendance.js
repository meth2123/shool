// Fonction pour formater une date en français
function formatDate(dateStr) {
    const date = new Date(dateStr);
    const options = { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        weekday: 'long'
    };
    return date.toLocaleDateString('fr-FR', options);
}

// Fonction pour formater une ligne de présence/absence
function formatAttendanceRow(data) {
    return `
        <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${data.date_formatted} - ${data.course_name}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                    data.status === 'present' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                }">
                    ${data.status === 'present' ? 'Présent' : 'Absent'}
                </span>
            </td>
        </tr>
    `;
}

// Fonction pour afficher un message d'erreur
function showError(tableId, message) {
    const tbody = document.querySelector(`#${tableId} tbody`);
    tbody.innerHTML = `
        <tr>
            <td colspan="2" class="px-6 py-4 text-center text-sm text-gray-500">
                <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
                ${message}
            </td>
        </tr>
    `;
}

// Fonction pour afficher un message de chargement
function showLoading(tableId) {
    const tbody = document.querySelector(`#${tableId} tbody`);
    tbody.innerHTML = `
        <tr>
            <td colspan="2" class="px-6 py-4 text-center text-sm text-gray-500">
                <i class="fas fa-spinner fa-spin mr-2"></i>
                Chargement en cours...
            </td>
        </tr>
    `;
}

// Fonction pour afficher un message "aucune donnée"
function showNoData(tableId) {
    const tbody = document.querySelector(`#${tableId} tbody`);
    tbody.innerHTML = `
        <tr>
            <td colspan="2" class="px-6 py-4 text-center text-sm text-gray-500">
                Aucune donnée disponible
            </td>
        </tr>
    `;
}

// Fonction pour charger les présences
function loadPresence(period = 'thismonth') {
    const studentId = $('#childid').val();
    if (!studentId) return;

    showLoading('mypresent');

    $.ajax({
        url: '../../service/attendance_service.php',
        type: 'POST',
        data: {
            action: 'get_presence',
            student_id: studentId,
            period: period
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const tbody = document.querySelector('#mypresent tbody');
                if (response.data && response.data.length > 0) {
                    tbody.innerHTML = response.data.map(formatAttendanceRow).join('');
                } else {
                    showNoData('mypresent');
                }
            } else {
                showError('mypresent', response.message || 'Erreur lors du chargement des données');
            }
        },
        error: function() {
            showError('mypresent', 'Erreur de communication avec le serveur');
        }
    });
}

// Fonction pour charger les absences
function loadAbsence(period = 'thismonth') {
    const studentId = $('#childid').val();
    if (!studentId) return;

    showLoading('myabsent');

    $.ajax({
        url: '../../service/attendance_service.php',
        type: 'POST',
        data: {
            action: 'get_absence',
            student_id: studentId,
            period: period
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const tbody = document.querySelector('#myabsent tbody');
                if (response.data && response.data.length > 0) {
                    tbody.innerHTML = response.data.map(formatAttendanceRow).join('');
                } else {
                    showNoData('myabsent');
                }
            } else {
                showError('myabsent', response.message || 'Erreur lors du chargement des données');
            }
        },
        error: function() {
            showError('myabsent', 'Erreur de communication avec le serveur');
        }
    });
}

// Initialisation des événements lors du chargement de la page
$(document).ready(function() {
    // Événements pour les boutons radio des présences
    $('input[name="present"]').change(function() {
        loadPresence($(this).val());
    });

    // Événements pour les boutons radio des absences
    $('input[name="absent"]').change(function() {
        loadAbsence($(this).val());
    });

    // Événement pour le changement d'élève
    $('#childid').change(function() {
        loadPresence($('input[name="present"]:checked').val());
        loadAbsence($('input[name="absent"]:checked').val());
    });

    // Chargement initial
    loadPresence();
    loadAbsence();
});

// Fonctions exportées
window.loadCurrentMonthPresence = function() {
    const studentId = $('#childid').val();
    loadPresence('thismonth');
};

window.loadAllPresence = function() {
    const studentId = $('#childid').val();
    loadPresence('all');
};

window.loadCurrentMonthAbsence = function() {
    const studentId = $('#childid').val();
    loadAbsence('thismonth');
};

window.loadAllAbsence = function() {
    const studentId = $('#childid').val();
    loadAbsence('all');
}; 