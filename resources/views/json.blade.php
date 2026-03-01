@extends('layouts.base')

{{-- Page Content --}}
@section('page_content')
    
    {{-- Page Title --}}
    {{-- @include('includes.title') --}}

    <?php
        
        //print "</pre>";

        /*
        $finalTables = [];
        $jsonFile = public_path().'/pdf_info3/info.json';
        if (file_exists($jsonFile))
        {
            $jsonString = file_get_contents($jsonFile);
            $jsonData   = json_decode($jsonString, true);

            $prepareTables = [];

            $prevTableNumber = 0;

            foreach($jsonData['tables'] as $currentTable)
            {
                //$currentTable    = $jsonData['tables'][0];
                $tableStructure  = $currentTable['structure'] ?? [];

                dump($currentTable, $currentTable['page']);

                //if ($prevTableNumber == 0 || $currentTable[])
            }

            foreach($jsonData['tables'] as $currentTable)
            {
                //$currentTable    = $jsonData['tables'][0];
                $tableStructure  = $currentTable['structure'] ?? [];
                $tableHasHeader  = $tableStructure['has_header'] ?? [];
                $tableHeaderRows = $tableStructure['header_rows'] ?? [];
                $tableHeader     = $tableStructure['columns_info'] ?? [];
                $tableBody       = $currentTable['data'] ?? [];

                $tableTop    = (int)round($currentTable['bbox'][3], 0);
                $tableBottom = (int)round($currentTable['bbox'][1], 0);

                //dump($tableY);
                //dump($currentTable);

                $finalTable = ['top' => $tableTop, 'bottom' => $tableBottom, 'header' => [], 'body' => []];

                foreach($tableHeader as $colNumber => $param)
                {
                    $finalTable['header'][$colNumber] = trim($param['header_name']) ?? '';
                }

                foreach($tableBody as $rowNumber => $row)
                {
                    $isHeader = false;
                    $isSkip   = false;
                    $count    = 0;
                    $values   = 0;


                    foreach($row as $colNumber => $value)
                    {
                        $value = trim($value);
                        if ($value <> "") $values++;
                        if (isset($finalTable['header'][$colNumber]) && $finalTable['header'][$colNumber] == $value) $count++;
                    }

                    if ($count > 0 && $count == count($finalTable['header']))
                    {
                        $isHeader = true;
                        $isSkip   = true;
                    }
                    elseif(isset($row[0]) && $row[0] <> "" && $values == 1)
                    {
                        $isHeader = true;
                    }

                    if (!$isSkip)
                    {
                        $finalTable['body'][] =
                        [
                            'class' => $isHeader ? ' class="table-active"' : '',
                            'data'  => $row,
                        ];
                    }
                }

                if (count($finalTable['body']) > 0)
                    $finalTables[] = $finalTable;
            }
            

            //dump($jsonData['tables'][0]);
        }
        */

        $finalTables = [];
    ?>

    <?php /*
    <div class="card mt-3">
        <div class="card-body">
            <h4 class="header-title">ТЕХНИЧЕСКИЕ ХАРАКТЕРИСТИКИ</h4>
            {{--
            <p class="sub-header font-13">
                Use one of two modifier classes to make <code>&lt;thead&gt;</code>s appear light
                or dark gray.
            </p>
            --}}
            <?php foreach($finalTables as $finalTable) { ?>
            <div class="table-responsive">
                <?php echo 'top: '.$finalTable['top']; ?>
                <?php echo 'bottom: '.$finalTable['bottom']; ?>
                <table class="table mt-2 mb-0">
                    <thead class="table-light">
                        <tr>
                        <?php foreach($finalTable['header'] as $row) {
                        echo "<th class=\"text-center\">{$row}</th>";
                        } ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($finalTable['body'] as $row) {
                            echo "<tr{$row['class']}>";
                            foreach($row['data'] as $colNumber => $value)
                            {
                                $class = ($colNumber > 0) ? ' class="text-center"' : '';
                                echo "<td{$class}>{$value}</td>";
                            }
                            echo "</tr>";
                        } ?>
                    </tbody>
                </table>
            </div> <!-- end table-responsive-->
            <?php } ?>

        </div>
    </div>
    */ ?>

@endsection


{{-- Page Java Script --}}
@section('page_java_script')

    

@endsection


{{-- Page More Java Script --}}
@section('page_more_java_script')

    <script>

        $(document).ready(function(){
            
            
            
        });

    </script>

@endsection

