<?php

/**
 * Class CSSFileUploadReader
 * This class should handle all of the logic for reading an uploaded file
 * (not necessarily parsing it yet).
 *
 * We check to see that the upload succeeded and do some light validation
 * (size, MIME type) in this class. This does NOT check for proper CSS syntax.
 *
 */
class CSSFileUploadReader
{
    const MAX_FILE_SIZE_IN_BYTES = 5000000; // 5MB. This belongs in config, really.

    private static $allowedFileExtensions = [
        'css',
    ];

    /**
     * Looks for a file in the $_FILES superglobal and attempts to read and
     * return the contents. In particular, we're looking for the element 'userfile'
     * as submitted by a form-data POST request.
     *
     * @return string
     * @throws IncorrectFiletypeException
     * @throws UploadFailedException
     * @throws UploadFileTooLargeException
     */
    public function readFile()
    {
        $this->validateFileUpload();

        // This reads the entire file into memory.
        // Only letting this happen because we have constrained the filesize already.
        return file_get_contents($_FILES['cssfile']['tmp_name']);
    }

    /**
     * Throws if the uploaded file fails any of the validation criteria.
     * This function has an implicit precedence order which it uses to validate the
     * upload request. The order is completely arbitrary, but if there are multiple
     * problems with a request, we only throw to show the first one we encounter.
     *
     * An alternative way of doing this would be to aggregate and encode a list of
     * errors and return / throw at the end.
     *
     * @throws IncorrectFiletypeException
     * @throws UploadFailedException
     * @throws UploadFileTooLargeException
     * @return void
     */
    private function validateFileUpload()
    {
        if (!$this->isFileUploadWellFormed()) {
            /**
             * Deliberately vague here. In a sophisticated application, we'd probably map
             * UPLOAD_ERR_* constants to something that could be displayed by the view, but
             * we're keeping this lightweight for now.
             */
            throw new UploadFailedException('File upload failed for unknown reason.');
        }

        if (!$this->isFileTypeCorrect()) {
            throw new IncorrectFiletypeException('Submitted file type is not supported.');
        }

        if (!$this->isFileSizeWithinBound()) {
            throw new UploadFileTooLargeException('The file uploaded exceeds the size limit.');
        }
    }

    /**
     * Verifies that the file was uploaded successfully, purely from the
     * perspective of successful data transit.
     *
     * Checks the PHP $_FILES superglobal for errors and makes sure the referenced file we will
     * be working with is uploaded (i.e. not maliciously pointing to existing files on server).
     *
     * @return bool
     */
    private function isFileUploadWellFormed()
    {
        $isFileUploadWellFormed = false;
        if (isset($_FILES['cssfile'])) {
            // Upload error flag is OK && not being asked to inspect file already on disk
            $isFileUploadWellFormed = $_FILES['cssfile']['error'] == UPLOAD_ERR_OK
                && is_uploaded_file($_FILES['cssfile']['tmp_name']);
        }
        return $isFileUploadWellFormed;
    }

    /**
     * Verifies that the uploaded file is within our size bound.
     *
     * @NOTE: This is here in case there is a divergence between the value
     * configured in php.ini and the CSS file reader. Absolute ceiling must be modified
     * in php.ini to increase this.
     * @return bool
     */
    private function isFileSizeWithinBound()
    {
        $isFileSizeWithinBound = false;
        if (isset($_FILES['cssfile'])) {
            $isFileSizeWithinBound = $_FILES['cssfile']['size'] <= self::MAX_FILE_SIZE_IN_BYTES;
        }
        return $isFileSizeWithinBound;
    }

    /**
     * Checks whether or not the extension on the submitted filename matches
     * the allowed extensions. Alternatively (or perhaps additionally) we may want
     * to check MIME type here.
     * @return bool
     */
    private function isFileTypeCorrect()
    {
        $isFileTypeCorrect = false;
        if (isset($_FILES['cssfile'])) {
            $fileExtension = pathinfo($_FILES['cssfile']['name'], PATHINFO_EXTENSION);
            $isFileTypeCorrect = in_array($fileExtension, self::$allowedFileExtensions);
        }
        return $isFileTypeCorrect;
    }
}

class IncorrectFiletypeException extends Exception {}
class UploadFileTooLargeException extends Exception {}
class UploadFailedException extends Exception {}