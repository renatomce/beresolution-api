<?php 

set_time_limit(0);

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
            if($control < 3) {
                switch($control) {
                    case 0:
                        $key = 'idCaixa';
                        break;
                    case 1:
                        $key = 'cnpj';
                        break;
                    case 2:
                        $key = 'dataFech';
                        break;
                }
                $dataArr['header'][$key] = implode('', $row);
            } else if ($control < 5) {
                switch ($control) {
                    case 3:
                        $key = 'gruposEstoque';
                        break;
                    case 4:
                        $key = 'saldosCaixa';
                        break;
                }
                $dataArr['detalhe'][$key][$row[0]] = $row[1];
            } else {
                switch ($control) {
                    case 5:
                        $dataArr['detalhe']['gruposEstoque']['total'] = implode('', $row);
                        break;
                    case 6:
                        $dataArr['detalhe']['saldosCaixa']['total'] = implode('', $row);
                        break;
                }
            }
        }
        $control++;
    }
    return $dataArr;
}

function createQuery($id) {
    $idQuery = 'SELECT ID FROM FCX_CAIXAS_MOVTOS WHERE ID = '. $id;
    $cnpj = 'SELECT CNPJ FROM SIS_PARAMETROS;';
    $date = 'SELECT FECH_DATA FROM FCX_CAIXAS_MOVTOS WHERE ID = '. $id;
    $groups = 'SELECT I_GRUPO, SUM(I_VLR_TOTAL) FROM V_EST_VENDAS_ITENS
        WHERE V_ID_CAIXA = '. $id .'
        GROUP BY I_GRUPO;';
    $balance = 'SELECT ID_FORMA_PAGTO, SUM(SALDO) FROM V_FCX_SALDOS
        WHERE ID_CAIXA = '. $id .'
        GROUP BY ID_FORMA_PAGTO;';
    $groupsTotal = 'SELECT SUM(I_VLR_TOTAL) FROM V_EST_VENDAS_ITENS
        WHERE V_ID_CAIXA = '. $id;
    $balanceTotal = 'SELECT SUM(SALDO) FROM V_FCX_SALDOS
        WHERE ID_CAIXA = '. $id;
    return array($idQuery, $cnpj, $date, $groups, $balance, $groupsTotal, $balanceTotal);
}

function execute() {
    $id = 'SELECT COUNT(ID) FROM FCX_CAIXAS_MOVTOS WHERE FECH_DATA IS NOT NULL;';
    $query = array($id);
    $id = queryExec($query);
    $id = $id['header']['idCaixa'];
    $newFiles = array();
    for ($i = 1; $i <= $id; $i++) {
        @$handle = fopen('files/'. $i .'.json', 'x');
        if ($handle !== false) {
            $queries = createQuery($i);
            $result = queryExec($queries);
            $newFiles[] = $result;
            $json = json_encode($result);
            fwrite($handle, $json);
        }
    }
    if (count($newFiles) !== 0) {
        header('Content-Type: application/json');
        echo json_encode($newFiles);
    } else {
        header('X-PHP-Response-Code: 204', true, 204);
    }
}

execute();

ibase_close($conn);

?>
