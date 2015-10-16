<?php

/**
 * Class ReportAndCSSPersistence
 * This class provides functions to handle persistence storage
 * of reports and uploaded CSS via the cssupload entrypoint.
 *
 * For now, we persist to Amazon S3, but theoretically we can swap
 * out the persistence layer (or provide multiple layers by changing
 * the persistence part to being an interface instead).
 */
class ReportAndCSSPersistence
{

    private $sessionUUID;

    /**
     * @param string $sessionUUID - a unique session ID for the upload
     */
    public function __construct($sessionUUID)
    {
        // For our purposes, this is random enough.
        // We aren't trying to hide any IDs, just trying to avoid collisions.
        $this->sessionUUID = $sessionUUID;
    }

    /**
     * @param string $reportString
     * @return string
     * @throws CreateReportFileFailedException
     * @throws S3BucketNotFoundException
     */
    public function persistCSSReport($reportString)
    {
        // TODO: Make sure sys_get_temp_dir works correctly on all platforms this application runs on
        $filePath = sys_get_temp_dir() . '/' .  $this->sessionUUID . '.json';
        $writeSuccess = file_put_contents($filePath, $reportString);
        if (!$writeSuccess) {
            throw new CreateReportFileFailedException('Failed to write report file to disk');
        }
        return $this->persistFileToS3($filePath, 'json');
    }

    /**
     * @param string $cssPath
     * @return string
     * @throws S3BucketNotFoundException
     */
    public function persistUploadedCSSFile($cssPath)
    {
        return $this->persistFileToS3($cssPath, 'css');
    }

    /**
     * Send the file to S3 for persistence.
     *
     * If we want to just store it locally to the server receiving the request,
     * simply use move_uploaded_file() to save the uploaded CSS and write
     * the report to the same location in a similar fashion.
     *
     * @param string $filePath
     * @param string $fileExtension
     * @return string
     * @throws S3BucketNotFoundException
     */
    private function persistFileToS3($filePath, $fileExtension)
    {
        $s3Client = Aws\S3\S3Client::factory();
        $s3Bucket = getenv('S3_BUCKET');
        if (!$s3Bucket) {
            throw new S3BucketNotFoundException('S3 persistent storage missing bucket in configuration.');
        }
        $fileName = $this->sessionUUID . '.' . $fileExtension;
        $uploadHandle = $s3Client->upload($s3Bucket, $fileName, fopen($filePath, 'rb'), 'public-read');
        return htmlspecialchars($uploadHandle->get('ObjectURL'));
    }
}

class S3BucketNotFoundException extends Exception {}
class CreateReportFileFailedException extends Exception {}