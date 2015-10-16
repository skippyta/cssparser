<?php

/**
 * Class CSSFileParser
 * This class is responsible for tokenizing an input CSS string and
 * abstracting information about it to be output in a report.
 */
class CSSFileParser
{
    /**
     * This deserves some explanation. Let's break this down into subsections:
     *
     * "([a-zA-Z0-9\s\,\.#\:]+)" matches some subset of CSS selector syntax.
     * That is, it matches alphanumeric ASCII characters, spaces, commas, periods, etc.
     * We group this into parentheses so that preg_match_all (used in junction with this)
     * breaks out the selector portion of a match into a separate array.
     *
     * "\{([^\}]*)\}" matches the rule / attribute block. It looks for an open bracket
     * and 0 or more non-close-bracket characters, followed by a close-bracket. Here,
     * we group the internal part of the attribute block in parentheses again so that
     * preg_match_all breaks this out into a separately indexed match array.
     *
     * Frankly, this is sketchy at best. The UTF-8 charset is not supported in this way,
     * nor are various other legitimate CSS constructs such as comments or string
     * literals containing close brackets. I make note of this below, but the real
     * tool to solve this problem would probably be a lexer-parser of some kind.
     *
     * This also does not detect or react well to malformed CSS files. In that way,
     * a regex is a pretty poor tool to solve this problem, but for the limited scope
     * of our needs here, it should suffice.
     */
    const CSS_TOKENIZING_REGEX = '/([a-zA-Z0-9\s\,\.#\:]+)\{([^\}]*)\}/';

    private $descriptorMetaInfo;
    private $selectorMetaInfo;

    // Which attribute keys to collect unique values for
    // We could actually make this a user-input, but it's static for now.
    private static $uniqueValueAggregateKeys = [
        'background',
        'color',
        'font-size',
        'font-family',
    ];

    public function __construct()
    {
        $this->descriptorMetaInfo['attributeCountsByType'] = [];
        $this->descriptorMetaInfo['uniqueAttributeValuesByType'] = [];
        $this->selectorMetaInfo = [];
    }

    /**
     * Generates a report about the provided CSS string.
     * The payload of the report is structured as follows:
     *
     * [
     *      'descriptorMetaInfo' => [
     *          'attributeCountsByType' => [
     *              'attribute1' => 6,
     *              'attribute2' => 5,
     *              ...
     *          ],
     *          'uniqueAttributeValuesByType' => [
     *              'font-face' => ['Arial', 'Sans Serif', ....],
     *              'background' => ['white', 'blue', 'green'...],
     *              ...
     *          ],
     *      ],
     *      'selectorMetaInfo' => [
     *          'numSelectors' => 5
     *      ],
     * ]
     *
     *
     * @param string $rawCSSString
     * @return array
     */
    public function generateReport($rawCSSString)
    {
        $this->processCSS($rawCSSString);
        $reportPayload = [
            'descriptorMetaInfo' => $this->descriptorMetaInfo,
            'selectorMetaInfo' => $this->selectorMetaInfo,
        ];
        return $reportPayload;
    }


    /**
     * Tokenize the CSS string into selectors and description blocks.
     * From there, we process them into the metadata arrays to print in
     * the report.
     *
     * @NOTE: As a first pass, this only works with very elementary CSS files,
     * specifically where selectors are only single elements (no commas, spaces, etc).
     * To really solve this problem in a robust and exhaustive manner, it's very likely that
     * the right tool is a lexer/parser such as bison. We really want to define a grammar,
     * not use hacky, brute force tools like "explode()" and such.
     *
     * @param $rawCSSString
     * @return void
     */
    private function processCSS($rawCSSString)
    {
        $regexMatches = [];
        $matchedSuccessfully = preg_match_all(self::CSS_TOKENIZING_REGEX, $rawCSSString, $regexMatches);
        if ($matchedSuccessfully) {
            $selectors = $regexMatches[1];
            $descriptors = $regexMatches[2];

            $this->processSelectors($selectors);
            $this->processDescriptors($descriptors);
        }
    }

    /**
     * Returns an array formatted version of the CSS string indexed
     * by the selector. The value located at each selector is a string descriptor
     * that looks like
     * "font-size: 12px; font-face: Arial;...."
     *
     * This is meant to allow for things like searching for values of particular selectors.
     * For now, this is unused.
     *
     * @param array $selectors
     * @param array $descriptors
     * @return array
     */
    private function interleaveSelectorsAndDescriptors(array $selectors, array $descriptors)
    {
        $numBlocks = count($selectors);
        $interleavedResult = [];
        for ($i = 0; $i < $numBlocks; $i++) {
            $selector = trim($selectors[$i]);
            $interleavedResult[$selector] = trim($descriptors[$i]);
        }

        return $interleavedResult;
    }

    /**
     * @param array $selectors
     * @return void
     */
    private function processSelectors(array $selectors)
    {
        $this->selectorMetaInfo['numSelectors'] = count($selectors);
    }

    /**
     * Function for gathering metadata about descriptors.
     * @param array $descriptors
     * @return void
     */
    private function processDescriptors(array $descriptors)
    {
        foreach ($descriptors as $descriptor) {
            // At this point, we're looking at the entire list of things
            // inside of the brackets, separated by semicolons.
            // Trim it, explode it, then explode again on the inner colons.
            $attributes = explode(';', trim($descriptor));
            foreach ($attributes as $attribute) {
                if ($attribute) {
                    $parsedAttribute = explode(':', $attribute);
                    $attributeKey = trim($parsedAttribute[0]);
                    $attributeValue = trim($parsedAttribute[1]);
                    $this->collectUniqueAttributeValueIfNecessary($attributeKey, $attributeValue);
                    $this->aggregateAttributeCountByType($attributeKey);
                }
            }
        }
        $this->dedupeAttributeValues();
    }

    private function dedupeAttributeValues()
    {
        $attributeValuesByType = $this->descriptorMetaInfo['uniqueAttributeValuesByType'];
        foreach ($attributeValuesByType as $attributeKey => $values) {
            // The attribute values are initially a list for each attribute key
            // Flipping them will make the keys unique, at which point we just take the keys again.
            $attributeValuesByType[$attributeKey] = array_keys(array_flip($values));
        }
    }

    /**
     * @param string $attributeKey
     * @param string $attributeValue
     * @return void
     */
    private function collectUniqueAttributeValueIfNecessary($attributeKey, $attributeValue)
    {
        $uniqueAttributeValuesByType = $this->descriptorMetaInfo['uniqueAttributeValuesByType'];
        if (in_array($attributeKey, self::$uniqueValueAggregateKeys)) {
            if (!isset($uniqueAttributeValuesByType[$attributeKey])) {
                $uniqueAttributeValuesByType[$attributeKey] = [];
            }
            $uniqueAttributeValuesByType[$attributeKey][] = $attributeValue;
        }
        $this->descriptorMetaInfo['uniqueAttributeValuesByType'] = $uniqueAttributeValuesByType;
    }

    /**
     * @param string $attributeKey
     * @return void
     */
    private function aggregateAttributeCountByType($attributeKey)
    {
        $attributeCountsByType = $this->descriptorMetaInfo['attributeCountsByType'];

        if (!isset($attributeCountsByType[$attributeKey])) {
            $attributeCountsByType[$attributeKey] = 0;
        }
        $attributeCountsByType[$attributeKey] += 1;

        $this->descriptorMetaInfo['attributeCountsByType'] = $attributeCountsByType;
    }
}