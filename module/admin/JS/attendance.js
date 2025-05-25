document.addEventListener("DOMContentLoaded", function() {
    // Show/hide loading spinner
    function toggleLoading(show) {
        const loadingSpinner = document.getElementById("loading-spinner");
        if (loadingSpinner) {
            loadingSpinner.classList.toggle("d-none", !show);
        }
    }

    // Generic error handler for AJAX requests
    function handleAjaxError(elementId, error) {
        console.error("AJAX error:", error);
        document.getElementById(elementId).innerHTML = 
            `<div class="alert alert-danger">
                <p class="fw-medium">Une erreur est survenue</p>
                <p class="small">${error.message || "Erreur lors du chargement des données"}</p>
            </div>`;
    }

    window.ajaxRequestToGetAttendanceTeacherPresentThisMonth = function() {
        var teacherId = document.getElementById("teaid").value;
        if (!teacherId) {
            console.log("No teacher selected");
            return;
        }
        
        toggleLoading(true);
        fetch("myattendanceteacherthismonth.php", {
            method: "POST",
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${encodeURIComponent(teacherId)}`
        })
        .then(response => response.json())
        .then(response => {
            console.log("Teacher presence response:", response);
            var html = "";
            if (response.error) {
                handleAjaxError("myteapresent", { message: response.error });
            } else if (response.records && response.records.length > 0) {
                // Créer une liste plus attrayante pour les présences
                html += '<div class="list-group">';
                response.records.forEach(function(record) {
                    const date = new Date(record.date);
                    const formattedDate = date.toLocaleDateString("fr-FR");
                    const formattedTime = date.toLocaleTimeString("fr-FR", {hour: '2-digit', minute:'2-digit'});
                    
                    html += `<div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <span class="fw-medium">${formattedDate}</span>
                        </div>
                        <div>
                            <span class="badge bg-success rounded-pill">${formattedTime}</span>
                        </div>
                    </div>`;
                });
                html += '</div>';
            } else {
                html = '<div class="alert alert-light text-center">Aucune présence trouvée</div>';
            }
            document.getElementById("myteapresent").innerHTML = html;
            toggleLoading(false);
        })
        .catch(error => {
            handleAjaxError("myteapresent", error);
            toggleLoading(false);
        });
    };

    // Cette fonction a été supprimée car nous n'affichons plus les absences

    window.ajaxRequestToGetAttendanceStaffPresentThisMonth = function() {
        var staffId = document.getElementById("staffid").value;
        if (!staffId) {
            console.log("No staff member selected");
            return;
        }
        
        toggleLoading(true);
        fetch("myattendancestaffthismonth.php", {
            method: "POST",
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${encodeURIComponent(staffId)}`
        })
        .then(response => response.json())
        .then(response => {
            console.log("Staff presence response:", response);
            var html = "";
            if (response.error) {
                handleAjaxError("mystaffpresent", { message: response.error });
            } else if (response.records && response.records.length > 0) {
                // Créer une liste plus attrayante pour les présences
                html += '<div class="list-group">';
                response.records.forEach(function(record) {
                    const date = new Date(record.date);
                    const formattedDate = date.toLocaleDateString("fr-FR");
                    const formattedTime = date.toLocaleTimeString("fr-FR", {hour: '2-digit', minute:'2-digit'});
                    
                    html += `<div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <span class="fw-medium">${formattedDate}</span>
                        </div>
                        <div>
                            <span class="badge bg-success rounded-pill">${formattedTime}</span>
                        </div>
                    </div>`;
                });
                html += '</div>';
            } else {
                html = '<div class="alert alert-light text-center">Aucune présence trouvée</div>';
            }
            document.getElementById("mystaffpresent").innerHTML = html;
            toggleLoading(false);
        })
        .catch(error => {
            handleAjaxError("mystaffpresent", error);
            toggleLoading(false);
        });
    };

    // Cette fonction a été supprimée car nous n'affichons plus les absences

    var teacherSelect = document.getElementById("teaid");
    var staffSelect = document.getElementById("staffid");

    if (teacherSelect) {
        teacherSelect.addEventListener("change", function() {
            console.log("Teacher changed:", this.value);
            if (this.value) {
                ajaxRequestToGetAttendanceTeacherPresentThisMonth();
            } else {
                document.getElementById("myteapresent").innerHTML = '<div class="alert alert-light text-center">Sélectionnez un enseignant</div>';
            }
        });

        // Initialize with first teacher if any
        if (teacherSelect.options.length > 1) {
            teacherSelect.selectedIndex = 1;
            ajaxRequestToGetAttendanceTeacherPresentThisMonth();
        }
    }

    if (staffSelect) {
        staffSelect.addEventListener("change", function() {
            console.log("Staff changed:", this.value);
            if (this.value) {
                ajaxRequestToGetAttendanceStaffPresentThisMonth();
            } else {
                document.getElementById("mystaffpresent").innerHTML = '<div class="alert alert-light text-center">Sélectionnez un membre du personnel</div>';
            }
        });

        // Initialize with first staff if any
        if (staffSelect.options.length > 1) {
            staffSelect.selectedIndex = 1;
            ajaxRequestToGetAttendanceStaffPresentThisMonth();
        }
    }

    console.log("Event listeners and initialization completed");
});

// Modal functions
function showJustificationModal(studentId, date, time, justification) {
    document.getElementById("modal_student_id").value = studentId;
    document.getElementById("modal_date").value = date;
    document.getElementById("modal_course_time").value = time;
    document.getElementById("modal_justification").value = justification;
    
    // Bootstrap 5 modal show
    const justificationModal = new bootstrap.Modal(document.getElementById("justificationModal"));
    justificationModal.show();
}

function hideJustificationModal() {
    // Bootstrap 5 modal hide
    const justificationModal = bootstrap.Modal.getInstance(document.getElementById("justificationModal"));
    if (justificationModal) {
        justificationModal.hide();
    }
}
