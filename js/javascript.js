document.addEventListener('DOMContentLoaded', function() {
    console.log("DOMContentLoaded fired!");

    // Function to copy the code to clipboard
    window.copyCodeToClipboard = function(btn) {
        var parentDiv = btn.parentElement.parentElement; // Navigating to 'code-container' div
        var copyText = parentDiv.querySelector("pre > code"); // Finding <code> inside the <pre>

        if (!copyText) {
            console.error("Couldn't find the <code> block to copy from.");
            return;
        }

        var textArea = document.createElement("textarea");
        textArea.value = copyText.textContent;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand("Copy");
        textArea.remove();
        alert("Code copied to clipboard!");
    };

    // Form submission listener
    jQuery(document).on('submit', 'form[enctype="multipart/form-data"]', function(e) {
        console.log("Form submit event detected");
        if (!validateFile()) {
            console.warn("File validation failed");
            e.preventDefault();
        } else {
            console.log("Form submitted with file:", jQuery('#excel_file').val());
        }
    });

    // Featherlight excel upload popup logic
    jQuery(document).on('click', '.featherlight-excel-trigger', function(e) {
        console.log("Featherlight trigger clicked");
        e.preventDefault();
        openUploadPopup();
    });

    function openUploadPopup() {
        console.log("Opening upload popup");
        const popupContent = `
            <div id="excel-upload-popup" class="excel-popup-container">
                <div id="upload-modal">
                    <form method="post" enctype="multipart/form-data">
                        <label for="excel_file">Select file to import:</label>
                        <input type="file" name="excel_file" id="excel_file">
                        <input type="submit" value="Import">
                    </form>
                </div>
                <div id="popup-overlay"></div>
            </div>
        `;

        jQuery.featherlight(popupContent);
    }
});

