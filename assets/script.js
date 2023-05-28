
const fileListElement = document.getElementById('file-list');

function loadAllMedia() {
    fetch('/api.php?request=listFiles')
        .then(response => response.json())
        .then(data => {
            const htmlBlock = data.map(file => `
                <div class="media ${file.type.toLowerCase()}">
                <div class="element row">
                    <div class="col-3 p-0 media-wrapper">
                    <img src="${file.path}" alt="${file.type}" />
                    </div>
                    <div class="col-9">
                    <div class="description">
                        <h2>${file.type} file</h2>
                        <div class="meta">
                        <ul>
                        <li>${file.type} file</li>
                            ${file.type === 'Video' ? `<li>Video duration: ${file.duration || 'Not available'}</li>` : ''}
                        </ul>
                        </div>
                    </div>
                    </div>
                </div>
                </div>
            `).join('');
            fileListElement.innerHTML = htmlBlock;
            const mediaElements = document.querySelectorAll('.media');
            mediaElements.forEach(mediaElement => {
                mediaElement.addEventListener('click', () => {
                    window.open(mediaElement.querySelector('img').src, '_blank');
                });
            });
        })
        .catch(error => console.error(error));
}

document.addEventListener('DOMContentLoaded', function () {
    var uploadButton = document.getElementById('upload-media');
    var uploadNotification = document.querySelector('.upload-notification');

    uploadButton.addEventListener('click', function () {
        var fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.accept = 'image/jpeg,image/png,video/mp4';
        fileInput.style.display = 'none';

        document.body.appendChild(fileInput);

        fileInput.click();

        fileInput.addEventListener('change', function () {
            var file = fileInput.files[0];

            if (file) {
                if (!isValidFileFormat(file)) {
                    alert('Only JPEG, PNG, and MP4 files are allowed.');
                    return;
                }

                if (!isValidFileSize(file)) {
                    alert('File size should not exceed 10MB for images and 50MB for videos.');
                    return;
                }

                uploadButton.classList.add('processing');
                uploadNotification.classList.add('active');
                uploadFile(file);
            }

            document.body.removeChild(fileInput);
        });

        return false;
    });

    function isValidFileFormat(file) {
        var fileType = file.type;
        return /^image\/(jpeg|png)$/.test(fileType) || fileType === 'video/mp4';
    }

    function isValidFileSize(file) {
        var fileSize = file.size;
        if (file.type === 'image/jpeg' || file.type === 'image/png') {
            return fileSize <= 10 * 1024 * 1024;
        } else if (file.type === 'video/mp4') {
            return fileSize <= 50 * 1024 * 1024;
        }
        return false;
    }

    function uploadFile(file) {
        var formData = new FormData();
        formData.append('media', file);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/api.php?request=uploadFile');
        xhr.onload = function () {
            if (xhr.status === 200) {
                alert(xhr.responseText);
            } else {
                alert(xhr.status);
            }

            while (fileListElement.firstChild) {
                fileListElement.removeChild(fileListElement.firstChild);
            }

            loadAllMedia();
            uploadButton.classList.remove('processing');
            uploadNotification.classList.remove('active');

        };
        xhr.send(formData);
    }
});

loadAllMedia();