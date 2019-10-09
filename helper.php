<?php

include('../connections/cnn.php');

function queryExec($queries)
{
    $dataArr = array();
    $control = 0;
    foreach ($queries as $query)
    {
        $rid = @ibase_query ($query);
        $coln = ibase_num_fields($rid);
        $blobFields = array();
        for ($i = 0; $i < $coln; $i++)
        {
            $col_info = ibase_field_info($rid, $i);
            if ($col_info["type"] == "BLOB") $blobFields[$i] = $col_info["name"];
        } 
        while ($row = ibase_fetch_row($rid))
        {
            foreach ($blobFields as $field_num)
            {
                $blobid = ibase_blob_open($row[$field_num], null);
                $row[$field_num] = ibase_blob_get($blobid, 102400);
                ibase_blob_close($blobid);
            }
            switch ($control) {
                case 0:
                    $dataArr['gruposEstoque'][$row[0]] = utf8_encode($row[1]);
                    break;
                case 1:
                    $dataArr['formasPagto'][$row[0]] = utf8_encode($row[1]);
                    break;
            }
        }
        $control++;
    }
    return $dataArr;
}

$productCategories = 'SELECT ID, NOME FROM EST_GRUPOS ORDER BY ID;';
$paymentMethods = 'SELECT ID, NOME FROM FCX_FORMAS_PAGTOS ORDER BY ID;';

@$handle = fopen('files/helper.json', 'w');
$queries = array($productCategories, $paymentMethods);
$result = queryExec($queries);
$json = json_encode($result, JSON_FORCE_OBJECT);
fwrite($handle, $json);
header('Content-Type: application/json');
echo $json;

ibase_close($conn);

?>
