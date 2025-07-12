document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Task drag and drop functionality
    const tasks = document.querySelectorAll('.task-card');
    const columns = document.querySelectorAll('.kanban-column .tasks-list');

    tasks.forEach(task => {
        task.addEventListener('dragstart', () => {
            task.classList.add('dragging');
        });

        task.addEventListener('dragend', () => {
            task.classList.remove('dragging');
            // Here you would add AJAX call to update task position in database
        });
    });

    columns.forEach(column => {
        column.addEventListener('dragover', e => {
            e.preventDefault();
            const draggingTask = document.querySelector('.dragging');
            const afterElement = getDragAfterElement(column, e.clientY);
            
            if (afterElement) {
                column.insertBefore(draggingTask, afterElement);
            } else {
                column.appendChild(draggingTask);
            }
        });
    });

    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.task-card:not(.dragging)')];

        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    // Add task modal
    const addTaskButtons = document.querySelectorAll('.add-task');
    const taskModal = new bootstrap.Modal(document.getElementById('taskModal'));

    addTaskButtons.forEach(button => {
        button.addEventListener('click', function() {
            const columnId = this.closest('.kanban-column').dataset.columnId;
            document.getElementById('taskColumnId').value = columnId;
            taskModal.show();
        });
    });

    // Dark mode toggle
    const darkModeToggle = document.getElementById('darkModeToggle');
    if (darkModeToggle) {
        darkModeToggle.addEventListener('change', function() {
            document.body.classList.toggle('dark-mode');
            // Save preference to localStorage
            localStorage.setItem('darkMode', this.checked);
        });

        // Check for saved preference
        if (localStorage.getItem('darkMode') === 'true') {
            darkModeToggle.checked = true;
            document.body.classList.add('dark-mode');
        }
    }

    // Projects Page
document.getElementById('newProjectBtn')?.addEventListener('click', () => {
  // Open project creation modal
});

// Pipeline Drag & Drop
const pipelineStages = document.querySelectorAll('.pipeline-stage');
pipelineStages.forEach(stage => {
  stage.addEventListener('dragover', e => {
    e.preventDefault();
    // Add visual drop zone
  });
});

// Analytics Filter
document.getElementById('analyticsFilter')?.addEventListener('change', (e) => {
  const days = e.target.value;
  // Reload analytics data via AJAX
});

    // Mobile menu toggle
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.querySelector('.sidebar');

    if (mobileMenuToggle && sidebar) {
        mobileMenuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('mobile-show');
        });
    }
});