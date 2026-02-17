// ============================================
// COMPLAINT MANAGEMENT SYSTEM - COMPLETE JAVASCRIPT
// assets/js/script.js
// ============================================

// Wait for DOM to be fully loaded
document.addEventListener("DOMContentLoaded", function () {
  // ============================================
  // DARK MODE
  // ============================================

  const darkModeToggle = document.getElementById("darkModeToggle");
  const darkModeIcon = document.getElementById("darkModeIcon");
  const htmlElement = document.documentElement;

  // Check for saved theme preference or default to light mode
  const currentTheme = localStorage.getItem("theme") || "light";
  htmlElement.setAttribute("data-theme", currentTheme);

  // Update icon based on current theme
  function updateIcon(theme) {
    if (!darkModeIcon) return; // Safeguard when icon isn't present on the page
    if (theme === "dark") {
      darkModeIcon.classList.remove("bi-moon-stars-fill");
      darkModeIcon.classList.add("bi-sun-fill");
    } else {
      darkModeIcon.classList.remove("bi-sun-fill");
      darkModeIcon.classList.add("bi-moon-stars-fill");
    }
  }

  // Set initial icon (only if element exists)
  if (darkModeIcon) updateIcon(currentTheme);

  // Toggle dark mode
  if (darkModeToggle) {
    darkModeToggle.addEventListener("click", function () {
      const currentTheme = htmlElement.getAttribute("data-theme");
      const newTheme = currentTheme === "dark" ? "light" : "dark";

      htmlElement.setAttribute("data-theme", newTheme);
      localStorage.setItem("theme", newTheme);
      updateIcon(newTheme);
    });
  }

  // ============================================
  // MOBILE SIDEBAR TOGGLE
  // ============================================

  const sidebar = document.getElementById("sidebar");
  const mobileToggle = document.getElementById("mobileToggle");
  const mobileSidebarToggle = document.getElementById("mobileSidebarToggle");
  const sidebarOverlay = document.getElementById("sidebarOverlay");
  const sidebarCloseBtn = document.getElementById("sidebarCloseBtn");

  // Open sidebar
  function openSidebar() {
    if (sidebar) sidebar.classList.add("active");
    if (sidebarOverlay) sidebarOverlay.classList.add("active");
    document.body.style.overflow = "hidden"; // Prevent background scroll
  }

  // Close sidebar
  function closeSidebar() {
    if (sidebar) sidebar.classList.remove("active");
    if (sidebarOverlay) sidebarOverlay.classList.remove("active");
    document.body.style.overflow = ""; // Restore scroll
  }

  // Toggle sidebar
  function toggleSidebar(e) {
    if (e) e.stopPropagation();
    if (sidebar && sidebar.classList.contains("active")) {
      closeSidebar();
    } else {
      openSidebar();
    }
  }

  // Mobile toggle button (bottom right floating)
  if (mobileToggle) {
    mobileToggle.addEventListener("click", toggleSidebar);
  }

  // Navbar hamburger button
  if (mobileSidebarToggle) {
    mobileSidebarToggle.addEventListener("click", toggleSidebar);
  }

  // Close button inside sidebar
  if (sidebarCloseBtn) {
    sidebarCloseBtn.addEventListener("click", closeSidebar);
  }

  // Close when clicking overlay
  if (sidebarOverlay) {
    sidebarOverlay.addEventListener("click", closeSidebar);
  }

  // Close sidebar when clicking on a menu item on mobile
  if (sidebar) {
    const sidebarLinks = sidebar.querySelectorAll(".sidebar-menu a");
    sidebarLinks.forEach(function (link) {
      link.addEventListener("click", function () {
        if (window.innerWidth <= 768) {
          closeSidebar();
        }
      });
    });
  }

  // Close sidebar on window resize (if desktop)
  window.addEventListener("resize", function () {
    if (window.innerWidth > 768) {
      closeSidebar();
      document.body.style.overflow = ""; // Always restore on desktop
    }
  });

  // ============================================
  // AUTO-DISMISS ALERTS
  // ============================================

  setTimeout(function () {
    const alerts = document.querySelectorAll(".alert:not(.alert-permanent)");
    alerts.forEach(function (alert) {
      if (typeof bootstrap !== "undefined" && bootstrap.Alert) {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
      } else {
        alert.style.transition = "opacity 0.5s";
        alert.style.opacity = "0";
        setTimeout(function () {
          alert.remove();
        }, 500);
      }
    });
  }, 5000);

  // ============================================
  // CONFIRM DELETE ACTIONS
  // ============================================

  const deleteButtons = document.querySelectorAll(
    '.btn-delete, [data-action="delete"]',
  );
  deleteButtons.forEach(function (button) {
    button.addEventListener("click", function (e) {
      const message =
        this.getAttribute("data-confirm-message") ||
        "Are you sure you want to delete this item?";
      if (!confirm(message)) {
        e.preventDefault();
        return false;
      }
    });
  });

  // ============================================
  // FORM VALIDATION ENHANCEMENT
  // ============================================

  const forms = document.querySelectorAll(".needs-validation");
  forms.forEach(function (form) {
    form.addEventListener(
      "submit",
      function (event) {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add("was-validated");
      },
      false,
    );
  });

  // ============================================
  // TOOLTIPS INITIALIZATION
  // ============================================

  if (typeof bootstrap !== "undefined" && bootstrap.Tooltip) {
    const tooltipTriggerList = [].slice.call(
      document.querySelectorAll('[data-bs-toggle="tooltip"]'),
    );
    tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });
  }

  // ============================================
  // POPOVERS INITIALIZATION
  // ============================================

  if (typeof bootstrap !== "undefined" && bootstrap.Popover) {
    const popoverTriggerList = [].slice.call(
      document.querySelectorAll('[data-bs-toggle="popover"]'),
    );
    popoverTriggerList.map(function (popoverTriggerEl) {
      return new bootstrap.Popover(popoverTriggerEl);
    });
  }

  // ============================================
  // SMOOTH SCROLL FOR ANCHOR LINKS
  // ============================================

  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      const href = this.getAttribute("href");
      if (href !== "#" && href !== "" && document.querySelector(href)) {
        e.preventDefault();
        const target = document.querySelector(href);
        const offsetTop = target.offsetTop - 80; // Account for fixed header

        window.scrollTo({
          top: offsetTop,
          behavior: "smooth",
        });
      }
    });
  });

  // ============================================
  // TABLE ROW CLICK TO VIEW DETAILS
  // ============================================

  const clickableRows = document.querySelectorAll("tr[data-href]");
  clickableRows.forEach(function (row) {
    row.style.cursor = "pointer";
    row.addEventListener("click", function (e) {
      // Don't trigger if clicking on a button or link
      if (
        e.target.tagName !== "BUTTON" &&
        e.target.tagName !== "A" &&
        !e.target.closest("button") &&
        !e.target.closest("a")
      ) {
        window.location.href = this.getAttribute("data-href");
      }
    });
  });

  // ============================================
  // SEARCH INPUT CLEAR BUTTON
  // ============================================

  const searchInputs = document.querySelectorAll(
    'input[type="search"], input.search-input',
  );
  searchInputs.forEach(function (input) {
    // Add clear button functionality
    input.addEventListener("input", function () {
      if (this.value.length > 0) {
        this.style.paddingRight = "35px";
      } else {
        this.style.paddingRight = "12px";
      }
    });
  });

  // ============================================
  // DATA TABLE INITIALIZATION (if using DataTables plugin)
  // ============================================

  if (typeof $.fn.DataTable !== "undefined") {
    $(".data-table").DataTable({
      responsive: true,
      pageLength: 10,
      language: {
        search: "Search:",
        lengthMenu: "Show _MENU_ entries",
        info: "Showing _START_ to _END_ of _TOTAL_ entries",
        paginate: {
          first: "First",
          last: "Last",
          next: "Next",
          previous: "Previous",
        },
      },
    });
  }

  // ============================================
  // CHARACTER COUNTER FOR TEXTAREAS
  // ============================================

  const textareasWithCounter = document.querySelectorAll(
    "textarea[data-max-length]",
  );
  textareasWithCounter.forEach(function (textarea) {
    const maxLength = textarea.getAttribute("data-max-length");
    const counterId = textarea.getAttribute("data-counter-id");

    if (counterId) {
      const counter = document.getElementById(counterId);

      textarea.addEventListener("input", function () {
        const currentLength = this.value.length;
        counter.textContent = currentLength + " / " + maxLength;

        if (currentLength > maxLength * 0.9) {
          counter.classList.add("text-danger");
          counter.classList.remove("text-success");
        } else {
          counter.classList.add("text-success");
          counter.classList.remove("text-danger");
        }
      });
    }
  });

  // ============================================
  // FILTER FORM AUTO SUBMIT
  // ============================================

  const autoSubmitSelects = document.querySelectorAll(
    'select[data-auto-submit="true"]',
  );
  autoSubmitSelects.forEach(function (select) {
    select.addEventListener("change", function () {
      this.closest("form").submit();
    });
  });

  // ============================================
  // CLIPBOARD COPY FUNCTIONALITY
  // ============================================

  const copyButtons = document.querySelectorAll("[data-copy]");
  copyButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      const textToCopy = this.getAttribute("data-copy");

      // Create temporary textarea
      const tempTextarea = document.createElement("textarea");
      tempTextarea.value = textToCopy;
      tempTextarea.style.position = "fixed";
      tempTextarea.style.opacity = "0";
      document.body.appendChild(tempTextarea);

      // Select and copy
      tempTextarea.select();
      document.execCommand("copy");

      // Remove temporary textarea
      document.body.removeChild(tempTextarea);

      // Show feedback
      const originalText = this.innerHTML;
      this.innerHTML = '<i class="bi bi-check"></i> Copied!';
      this.classList.add("btn-success");

      setTimeout(() => {
        this.innerHTML = originalText;
        this.classList.remove("btn-success");
      }, 2000);
    });
  });

  // ============================================
  // BACK TO TOP BUTTON
  // ============================================

  // Create back to top button
  const backToTopBtn = document.createElement("button");
  backToTopBtn.innerHTML = '<i class="bi bi-arrow-up"></i>';
  backToTopBtn.className = "back-to-top";
  backToTopBtn.setAttribute("aria-label", "Back to top");
  document.body.appendChild(backToTopBtn);

  // Show/hide back to top button
  window.addEventListener("scroll", function () {
    if (window.pageYOffset > 300) {
      backToTopBtn.classList.add("show");
    } else {
      backToTopBtn.classList.remove("show");
    }
  });

  // Scroll to top on click
  backToTopBtn.addEventListener("click", function () {
    window.scrollTo({
      top: 0,
      behavior: "smooth",
    });
  });

  // ============================================
  // PREVENT DOUBLE FORM SUBMISSION
  // ============================================

  const allForms = document.querySelectorAll("form");
  allForms.forEach(function (form) {
    form.addEventListener("submit", function () {
      const submitBtn = this.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML =
          '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

        // Re-enable after 3 seconds in case of errors
        setTimeout(function () {
          submitBtn.disabled = false;
          submitBtn.innerHTML =
            submitBtn.getAttribute("data-original-text") || "Submit";
        }, 3000);
      }
    });
  });
});

// ============================================
// UTILITY FUNCTIONS (Global)
// ============================================

// Show loading spinner
function showLoading(elementId) {
  const element = document.getElementById(elementId);
  if (element) {
    element.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3 text-muted">Loading...</p>
            </div>
        `;
  }
}

// Hide loading spinner
function hideLoading(elementId) {
  const element = document.getElementById(elementId);
  if (element) {
    const spinner = element.querySelector(".spinner-border");
    if (spinner) {
      spinner.closest("div").remove();
    }
  }
}

// Show toast notification
function showToast(message, type = "info", duration = 3000) {
  const toastHTML = `
        <div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;

  // Create toast container if it doesn't exist
  let toastContainer = document.getElementById("toastContainer");
  if (!toastContainer) {
    toastContainer = document.createElement("div");
    toastContainer.id = "toastContainer";
    toastContainer.className =
      "toast-container position-fixed bottom-0 end-0 p-3";
    toastContainer.style.zIndex = "9999";
    document.body.appendChild(toastContainer);
  }

  // Add toast
  toastContainer.insertAdjacentHTML("beforeend", toastHTML);
  const toastElement = toastContainer.lastElementChild;
  const toast = new bootstrap.Toast(toastElement, {
    autohide: true,
    delay: duration,
  });
  toast.show();

  // Remove toast element after it's hidden
  toastElement.addEventListener("hidden.bs.toast", function () {
    toastElement.remove();
  });
}

// Confirm action
function confirmAction(message = "Are you sure?", callback) {
  if (confirm(message)) {
    if (typeof callback === "function") {
      callback();
    }
    return true;
  }
  return false;
}

// Format number with commas
function formatNumber(num) {
  return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// Validate email
function isValidEmail(email) {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return re.test(email);
}

// Validate phone number
function isValidPhone(phone) {
  const re = /^[0-9]{10,15}$/;
  return re.test(phone.replace(/[\s\-\(\)]/g, ""));
}

// Print specific element
function printElement(elementId) {
  const element = document.getElementById(elementId);
  if (element) {
    const printWindow = window.open("", "", "height=600,width=800");
    printWindow.document.write("<html><head><title>Print</title>");
    printWindow.document.write(
      '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">',
    );
    printWindow.document.write("</head><body>");
    printWindow.document.write(element.innerHTML);
    printWindow.document.write("</body></html>");
    printWindow.document.close();
    printWindow.print();
  }
}

// Export table to CSV
function exportTableToCSV(tableId, filename = "export.csv") {
  const table = document.getElementById(tableId);
  if (!table) return;

  let csv = [];
  const rows = table.querySelectorAll("tr");

  for (let i = 0; i < rows.length; i++) {
    const row = [];
    const cols = rows[i].querySelectorAll("td, th");

    for (let j = 0; j < cols.length; j++) {
      let data = cols[j].innerText
        .replace(/(\r\n|\n|\r)/gm, "")
        .replace(/(\s\s)/gm, " ");
      data = data.replace(/"/g, '""');
      row.push('"' + data + '"');
    }

    csv.push(row.join(","));
  }

  // Download CSV
  const csvString = csv.join("\n");
  const link = document.createElement("a");
  link.setAttribute(
    "href",
    "data:text/csv;charset=utf-8," + encodeURIComponent(csvString),
  );
  link.setAttribute("download", filename);
  link.style.display = "none";
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

// Force text colors in dark mode
document.addEventListener("DOMContentLoaded", function () {
  function updateDarkModeText() {
    const isDark =
      document.documentElement.getAttribute("data-theme") === "dark";

    if (isDark) {
      // Force all text elements to use correct colors
      document
        .querySelectorAll("p, span, div, td, th, li, label")
        .forEach((el) => {
          if (
            window.getComputedStyle(el).color === "rgb(51, 51, 51)" ||
            window.getComputedStyle(el).color === "rgb(0, 0, 0)"
          ) {
            el.style.color = "var(--text-primary)";
          }
        });
    }
  }

  // Run on load
  updateDarkModeText();

  // Run when dark mode toggles
  const darkModeToggle = document.getElementById("darkModeToggle");
  if (darkModeToggle) {
    darkModeToggle.addEventListener("click", function () {
      setTimeout(updateDarkModeText, 100);
    });
  }
});

// ============================================
// ADDITIONAL CSS FOR DYNAMIC ELEMENTS
// ============================================

// Add CSS for back to top button
const style = document.createElement("style");
style.innerHTML = `
    .back-to-top {
        position: fixed;
        bottom: 80px;
        right: 20px;
        width: 45px;
        height: 45px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s;
        z-index: 998;
        box-shadow: 0 3px 15px rgba(0,0,0,0.3);
    }
    
    .back-to-top.show {
        opacity: 1;
        visibility: visible;
    }
    
    .back-to-top:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.4);
    }
    
    .back-to-top i {
        font-size: 1.2rem;
    }
`;
document.head.appendChild(style);
