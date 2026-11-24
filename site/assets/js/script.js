document.addEventListener("DOMContentLoaded", function () {
    // Mobile sidebar toggle
    var btn = document.getElementById("menuBtn");
    var sidebar = document.getElementById("sidebar");
    if (btn && sidebar) {
        btn.addEventListener("click", function () {
            sidebar.classList.toggle("show");
        });
    }

    // Confirm before submitting delete forms
    document.querySelectorAll("form[data-confirm]").forEach(function (form) {
        form.addEventListener("submit", function (e) {
            if (!confirm(form.getAttribute("data-confirm"))) {
                e.preventDefault();
            }
        });
    });

    // Confirm before following links (logout)
    document.querySelectorAll("a[data-confirm-link]").forEach(function (a) {
        a.addEventListener("click", function (e) {
            if (!confirm(a.getAttribute("data-confirm-link"))) {
                e.preventDefault();
            }
        });
    });

    // Attendance: mark everyone with one click
    document.querySelectorAll("[data-mark-all]").forEach(function (b) {
        b.addEventListener("click", function () {
            var value = b.getAttribute("data-mark-all");
            document.querySelectorAll('input[type="radio"][value="' + value + '"]').forEach(function (r) {
                r.checked = true;
            });
        });
    });

    // Auto-dismiss success alerts
    document.querySelectorAll(".alert-success").forEach(function (a) {
        setTimeout(function () { a.style.display = "none"; }, 5000);
    });

    // Print buttons
    document.querySelectorAll("[data-print]").forEach(function (b) {
        b.addEventListener("click", function () { window.print(); });
    });
});
