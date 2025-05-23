// Show/hide loading spinner
function showLoading() {
	console.log("Loading spinner shown");
	var spinner = document.getElementById("loading-spinner");
	if (spinner) {
		spinner.classList.remove("hidden");
	} else {
		console.error("Loading spinner element not found");
	}
}

function hideLoading() {
	console.log("Loading spinner hidden");
	var spinner = document.getElementById("loading-spinner");
	if (spinner) {
		spinner.classList.add("hidden");
	} else {
		console.error("Loading spinner element not found");
	}
}

// Error handling function
function handleError(error) {
	console.error("Error:", error);
	alert("Une erreur est survenue. Veuillez réessayer plus tard.");
	hideLoading();
}

// Format date for display
function formatDate(dateStr) {
	try {
		const date = new Date(dateStr);
		return date.toLocaleDateString("fr-FR", {
			year: "numeric",
			month: "long",
			day: "numeric"
		});
	} catch (e) {
		console.error("Date formatting error:", e);
		return dateStr;
	}
}

// Update container content
function updateContainer(containerId, html) {
	console.log("Updating container:", containerId, "with HTML:", html);
	var container = document.getElementById(containerId);
	if (container) {
		container.innerHTML = html;
	} else {
		console.error("Container not found:", containerId);
	}
}

// Generic AJAX request function
function makeAjaxRequest(url, data, containerId, processData = null) {
	console.log("Starting AJAX request:", { url, data, containerId });
	showLoading();
	
	// Verify container exists
	var container = document.getElementById(containerId);
	if (!container) {
		console.error("Container not found:", containerId);
		hideLoading();
		return;
	}
	
	// Show loading state in container
	updateContainer(containerId, "<div class='text-gray-500 text-center'>Chargement en cours...</div>");
	
	$.ajax({
		url: url,
		data: data,
		method: "POST",
		dataType: "json"
	})
	.done(function(response) {
		console.log("Response received for", containerId, ":", response);
		try {
			if (response.error) {
				throw new Error(response.error);
			}
			
			var html = "";
			if (response.records && response.records.length > 0) {
				console.log("Processing", response.records.length, "records for", containerId);
				response.records.forEach(function(record) {
					html += "<div class='mb-2 p-2 " + 
						   (record.status === "Present" ? "text-green-600" : "text-red-600") + 
						   " border-b'>" +
						   formatDate(record.date) + " - " + record.status +
						   "</div>";
				});
			} else {
				html = "<div class='text-gray-500 text-center'>Aucun enregistrement trouvé</div>";
			}
			
			updateContainer(containerId, html);
			
			if (processData && typeof processData === "function") {
				processData(response);
			}
		} catch (error) {
			console.error("Error processing response for", containerId, ":", error);
			updateContainer(containerId, "<div class='text-red-500 text-center'>Erreur: " + error.message + "</div>");
			handleError(error);
		}
	})
	.fail(function(jqXHR, textStatus, errorThrown) {
		console.error("AJAX error for", containerId, ":", {
			status: jqXHR.status,
			textStatus: textStatus,
			errorThrown: errorThrown,
			responseText: jqXHR.responseText
		});
		updateContainer(containerId, "<div class='text-red-500 text-center'>Erreur de chargement</div>");
		handleError(errorThrown || textStatus);
	})
	.always(function() {
		hideLoading();
	});
}

// Teacher attendance functions
function ajaxRequestToGetAttendanceTeacherPresentThisMonth() {
	console.log("Getting teacher attendance");
	var teacherId = document.getElementById("teaid").value;
	if (!teacherId) {
		console.log("No teacher selected");
		updateContainer("myteapresent", "<div class='text-gray-500 text-center'>Veuillez sélectionner un enseignant</div>");
		updateContainer("myteaabsent", "<div class='text-gray-500 text-center'>Veuillez sélectionner un enseignant</div>");
		return;
	}
	
	makeAjaxRequest("myattendanceteacherthismonth.php", { id: teacherId }, "myteapresent", function() {
		ajaxRequestToGetAttendanceTeacherAbsentThisMonth();
	});
}

function ajaxRequestToGetAttendanceTeacherPresentAll() {
	const data = {
		id: $('#teaid').val(),
		type: 'present',
		period: 'all'
	};
	makeAjaxRequest('myattendanceteacherall.php', data, 'myteapresent');
}

function ajaxRequestToGetAttendanceTeacherAbsentThisMonth() {
	console.log("Getting teacher absences");
	var teacherId = document.getElementById("teaid").value;
	if (!teacherId) {
		console.log("No teacher selected");
		return;
	}
	
	makeAjaxRequest("myattendanceteacherabsentthismonth.php", { id: teacherId }, "myteaabsent");
}

function ajaxRequestToGetAttendanceTeacherAbsentAll() {
	const data = {
		id: $('#teaid').val(),
		type: 'absent',
		period: 'all'
	};
	makeAjaxRequest('myattendanceteacherabsentall.php', data, 'myteaabsent');
}

// Staff attendance functions
function ajaxRequestToGetAttendanceStaffPresentThisMonth() {
	console.log("Getting staff attendance");
	var staffId = document.getElementById("staffid").value;
	if (!staffId) {
		console.log("No staff member selected");
		updateContainer("mystaffpresent", "<div class='text-gray-500 text-center'>Veuillez sélectionner un membre du personnel</div>");
		updateContainer("mystaffabsent", "<div class='text-gray-500 text-center'>Veuillez sélectionner un membre du personnel</div>");
		return;
	}
	
	makeAjaxRequest("myattendancestaffthismonth.php", { id: staffId }, "mystaffpresent", function() {
		ajaxRequestToGetAttendanceStaffAbsentThisMonth();
	});
}

function ajaxRequestToGetAttendanceStaffPresentAll() {
	const data = {
		id: $('#staffid').val(),
		type: 'present',
		period: 'all'
	};
	makeAjaxRequest('myattendancestaffall.php', data, 'mystaffpresent');
}

function ajaxRequestToGetAttendanceStaffAbsentThisMonth() {
	console.log("Getting staff absences");
	var staffId = document.getElementById("staffid").value;
	if (!staffId) {
		console.log("No staff member selected");
		return;
	}
	
	makeAjaxRequest("myattendancestaffabsentthismonth.php", { id: staffId }, "mystaffabsent");
}

function ajaxRequestToGetAttendanceStaffAbsentAll() {
	const data = {
		id: $('#staffid').val(),
		type: 'absent',
		period: 'all'
	};
	makeAjaxRequest('myattendancestaffabsentall.php', data, 'mystaffabsent');
}

// Initialize when DOM is ready
document.addEventListener("DOMContentLoaded", function() {
	console.log("DOM ready - Initializing attendance system");
	
	// Add change event listeners
	var teacherSelect = document.getElementById("teaid");
	var staffSelect = document.getElementById("staffid");
	
	if (teacherSelect) {
		teacherSelect.addEventListener("change", function() {
			console.log("Teacher changed:", this.value);
			ajaxRequestToGetAttendanceTeacherPresentThisMonth();
		});
	} else {
		console.error("Teacher select not found");
	}
	
	if (staffSelect) {
		staffSelect.addEventListener("change", function() {
			console.log("Staff changed:", this.value);
			ajaxRequestToGetAttendanceStaffPresentThisMonth();
		});
	} else {
		console.error("Staff select not found");
	}
});