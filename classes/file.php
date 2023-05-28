<?php

class FileDetails {
    public function __construct(
        public string $path,
        public string $type,
        public ?string $duration = null
    ) {
    }
}

class FileLister {
    private string $folder;

    public function __construct(string $folder) {
        $this->folder = $folder;
    }

    public function getFiles(): array {
        $files = [];
        $filePaths = glob($this->folder . '/*');

        usort($filePaths, function ($a, $b) {
            return filemtime($b) <=> filemtime($a);
        });

        foreach ($filePaths as $filePath) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $type = finfo_file($finfo, $filePath);
            finfo_close($finfo);
            $duration = null;

            if (str_starts_with($type, 'video')) {
                $command = "ffmpeg -i " . escapeshellarg($filePath) . " 2>&1 | grep 'Duration' | head -1 | awk -F 'Duration:' '{ print $2 }' | awk -F ',' '{ print $1 }'";
                $durationTime = shell_exec($command);
                $durationTime = trim($durationTime);
                list($hours, $minutes, $seconds) = explode(":", substr($durationTime, 0, 8));
                $duration = sprintf("%s secs", $hours * 3600 + $minutes * 60 + $seconds);
                $files[] = new FileDetails($filePath, 'Video', $duration);
            } elseif (str_starts_with($type, 'image')) {
                $files[] = new FileDetails($filePath, 'Image');
            }
        }

        return $files;
    }
}


class FileUploader {
    private $uploadDir;

    public function __construct($uploadDir) {
        $this->uploadDir = $uploadDir;
    }

    public function uploadFile($file): string {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return 'Error uploading file. Error code: ' . $file['error'];
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            return 'Error: temporary file does not exist or is not an uploaded file.';
        }

        if (!is_readable($file['tmp_name'])) {
            return 'Error: temporary file is not readable.';
        }

        if (!is_dir($this->uploadDir)) {
            return 'Error: upload directory does not exist.';
        }

        $fileName = $file['name'];
        $fileType = $file['type'];
        $fileTmpPath = $file['tmp_name'];
        $uniqueFileName = uniqid() . '_' . $fileName;

        if (!move_uploaded_file($fileTmpPath, $this->uploadDir . '/' . $uniqueFileName)) {
            return 'Error: failed to move uploaded file.';
        }

        if ($fileType === 'video/mp4') {
            $originalFilePath = $this->uploadDir . '/' . $uniqueFileName;
            $compressedFilePath = $this->compressVideo($originalFilePath);
            if ($compressedFilePath) {
                if (!rename($compressedFilePath, $originalFilePath)) {
                    return 'Error: failed to replace original file with compressed file.';
                }
            } else {
                return 'Failed to compress video';
            }
        }

        return 'File uploaded successfully.';
    }

    private function compressVideo($filePath): ?string {
        $originalFileSize = filesize($filePath);
        $compressedFilePath = $filePath . '_compressed.mp4';
        $maxFileSize = $this->getMaxVideoFileSize($originalFileSize);

        $crf = 23;
        $compressedFileSize = $originalFileSize;

        while ($compressedFileSize > $maxFileSize && $crf <= 51) {
            $ffmpegCommand = "ffmpeg -y -i $filePath -vf 'scale=iw*0.8:ih*0.8' -c:v libx264 -crf $crf $compressedFilePath";
            exec($ffmpegCommand);

            clearstatcache();
            $compressedFileSize = filesize($compressedFilePath);

            if ($compressedFileSize === 0) {
                unlink($compressedFilePath);
                unlink($filePath);
                return null;
            }

            $crf += 2;
        }

        return $compressedFilePath;
    }

    private function getMaxVideoFileSize($originalFileSize): float {
        $maxFileSize = 0.8 * $originalFileSize;
        return $maxFileSize;
    }
}
