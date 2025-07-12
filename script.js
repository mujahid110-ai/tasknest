document.addEventListener('DOMContentLoaded', function() {
    // DOM elements
    const kanbanBoard = document.querySelector('.kanban-board');
    const addProjectForm = document.getElementById('addProjectForm');
    const taskForm = document.getElementById('taskForm');
    const taskModal = new bootstrap.Modal(document.getElementById('taskModal'));
    
    // Drag and drop functionality
    let draggedTask = null;
    
    // Add event listeners to all tasks
    function setupDragAndDrop() {
        const tasks = document.querySelectorAll('.kanban-task');
        
        tasks.forEach(task => {
            task.addEventListener('dragstart', function() {
                draggedTask = this;
                setTimeout(() => {
                    this.classList.add('dragging');
                }, 0);
            });
            
            task.addEventListener('dragend', function() {
                this.classList.remove('dragging');
            });
        });
        
        const lists = document.querySelectorAll('.kanban-list-body');
        
        lists.forEach(list => {
            list.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('dropzone');
                
                // Get the current position where the task is being dragged over
                const afterElement = getDragAfterElement(this, e.clientY);
                if (afterElement) {
                    this.insertBefore(draggedTask, afterElement);
                } else {
                    this.appendChild(draggedTask);
                }
            });
            
            list.addEventListener('dragleave', function() {
                this.classList.remove('dropzone');
            });
            
            list.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('dropzone');
                
                if (draggedTask) {
                    const listId = this.dataset.listId;
                    const taskId = draggedTask.dataset.taskId;
                    
                    // Get new position based on siblings
                    const tasks = Array.from(this.children);
                    const newPosition = tasks.indexOf(draggedTask);
                    
                    // Update task position in database
                    updateTaskPosition(taskId, listId, newPosition);
                }
            });
        });
    }
    
    // Helper function to determine where to place the dragged task
    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.kanban-task:not(.dragging)')];
        
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
    
    // Update task position via AJAX
    function updateTaskPosition(taskId, listId, position) {
        fetch('update_task_position.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                task_id: taskId,
                list_id: listId,
                position: position,
                csrf_token: document.querySelector('input[name="csrf_token"]').value
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('Failed to update task position');
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
    
    // Add Project Form
    addProjectForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('add_project.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.error || 'Failed to create project');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
        });
    });
    
    // Task Form
    taskForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const taskId = formData.get('task_id');
        const url = taskId ? 'update_task.php' : 'add_task.php';
        
        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.error || 'Failed to save task');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
        });
    });
    
    // Add Task Button
    document.querySelectorAll('.add-task-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('taskModalTitle').textContent = 'Add Task';
            document.getElementById('taskListId').value = this.dataset.listId;
            document.getElementById('taskId').value = '';
            document.getElementById('taskForm').reset();
            taskModal.show();
        });
    });
    
    // Edit Task Button
    document.querySelectorAll('.edit-task').forEach(btn => {
        btn.addEventListener('click', function() {
            const taskId = this.dataset.taskId;
            const taskElement = document.querySelector(`.kanban-task[data-task-id="${taskId}"]`);
            
            document.getElementById('taskModalTitle').textContent = 'Edit Task';
            document.getElementById('taskId').value = taskId;
            document.getElementById('taskListId').value = taskElement.closest('.kanban-list-body').dataset.listId;
            
            // Populate form with task data
            document.getElementById('taskTitle').value = taskElement.querySelector('h6').textContent;
            document.getElementById('taskDescription').value = taskElement.querySelector('.task-description')?.textContent || '';
            document.getElementById('taskPriority').value = taskElement.querySelector('.task-priority').className.split(' ')[1];
            
            const deadlineElement = taskElement.querySelector('.task-deadline');
            if (deadlineElement) {
                const deadlineText = deadlineElement.textContent.trim();
                const deadlineDate = new Date(deadlineText.split(', ')[1]);
                document.getElementById('taskDeadline').valueAsDate = deadlineDate;
            } else {
                document.getElementById('taskDeadline').value = '';
            }
            
            taskModal.show();
        });
    });
    
    // Delete Task Button
    document.querySelectorAll('.delete-task').forEach(btn => {
        btn.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this task?')) {
                const taskId = this.dataset.taskId;
                
                fetch('delete_task.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        task_id: taskId,
                        csrf_token: document.querySelector('input[name="csrf_token"]').value
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.error || 'Failed to delete task');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred');
                });
            }
        });
    });
    
    // Initialize drag and drop
    setupDragAndDrop();
});