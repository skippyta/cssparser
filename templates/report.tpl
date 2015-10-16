<html>
    <body>
        <h1>CSS File Report</h1>
        <p>You can download the JSON formatted report <a href="{$reportURL}">here</a>.</p>
        <p>You can download the original CSS  <a href="{$cssURL}">here</a>.</p>
        <p>Your session ID to retrieve these files in the future is: {$sessionID}</p>
        <p>There were a total of {$reportPayload.selectorMetaInfo.numSelectors} selectors in the uploaded stylesheet.</p>
        <p>Below is a list of all of the attributes and the number of times each one appears:</p>
        <ul>
            {foreach from=$reportPayload.descriptorMetaInfo.attributeCountsByType key=attribute item=count}
                <li>
                    <b>{$attribute}</b>: {$count}
                </li>
            {/foreach}
        </ul>
        <p>And below is a listing of unique values that appeared for a selection of attributes:</p>
        <ul>
            {foreach from=$reportPayload.descriptorMetaInfo.uniqueAttributeValuesByType key=attribute item=values}
                <li>
                    <b>{$attribute}</b>:
                    {foreach from=$values item=value name=ele}
                        {$value}{if not $smarty.foreach.ele.last}, {/if}
                    {/foreach}
                </li>
            {/foreach}
        </ul>
    </body>
</html>