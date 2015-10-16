<?php


/**
 * Class ParseCSSFileFlow
 * This class encapsulates the flow of parsing, generating,
 * and displaying a report for an uploaded CSS file (specifically via
 * the cssupload entrypoint).
 */
class ParseCSSFileFlow
{
    /**
     * @return Smarty
     * @throws CreateReportFileFailedException
     */
    public function execute()
    {
        // Unique Session ID for matching the storage filenames
        // of the report and CSS. Allows for later retrieval.
        // For our purposes, this is random enough (not cryptographically secure, though).
        // We aren't trying to hide any IDs, just trying to avoid collisions.
        $sessionID = uniqid(rand(), true);

        // Validate and read file
        $uploadReader = new CSSFileUploadReader();
        $fileContents = $uploadReader->readFile();

        // Parse file / generate report and write.
        $cssFileParser = new CSSFileParser();
        $reportPayload = $cssFileParser->generateReport($fileContents);
        $reportEncoded = json_encode($reportPayload);

        // Persist. For now, using Amazon since Heroku doesn't have a persistent filesystem.
        $persistenceHelper = new ReportAndCSSPersistence($sessionID);
        $reportURL = $persistenceHelper->persistCSSReport($reportEncoded);

        $cssFilePath = $_FILES['cssfile']['tmp_name'];
        $cssURL = $persistenceHelper->persistUploadedCSSFile($cssFilePath);

        // Assign smarty, return.
        $smarty = new Smarty();
        $smarty->assign('reportURL', $reportURL);
        $smarty->assign('cssURL', $cssURL);
        $smarty->assign('reportPayload', $reportPayload);
        $smarty->assign('sessionID', $sessionID);
        return $smarty;
    }
}