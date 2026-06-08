/* =========================================
   FILE UPLOAD DRAG & DROP LOGIC
   ========================================= */
document.addEventListener("DOMContentLoaded", function() {
    
    const fileInput = document.getElementById('proofUpload');
    const fileNameDisplay = document.getElementById('fileNameDisplay');
    const dropZone = document.getElementById('dropZone');

    // Only run this script if the upload zone actually exists on the page
    if (fileInput && dropZone) {
        
        // Show file name when selected via clicking
        fileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                fileNameDisplay.textContent = 'Selected File: ' + e.target.files[0].name;
                fileNameDisplay.style.display = 'block';
                dropZone.style.borderColor = 'var(--success-text)';
            }
        });

        // Visual feedback for Drag and Drop (Hover effect)
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });

        dropZone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
        });

        // Handle the actual drop
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            
            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                fileNameDisplay.textContent = 'Selected File: ' + e.dataTransfer.files[0].name;
                fileNameDisplay.style.display = 'block';
                dropZone.style.borderColor = 'var(--success-text)';
            }
        });
    }
});