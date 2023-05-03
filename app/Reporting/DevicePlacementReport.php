<?php

namespace App\Reporting;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Utils;

/*
    NOTES:

    Initially, I wanted to be able to write one query to handle Distributors, Subsidiaries and Lone Companies
    but after things got to complicated, I decided to split it based on mode.
*/

class DevicePlacementReport {

    public function summary(
        $user,
        $year,
        $company_ids,
        $mode
    ){
        // no selection, no data (the best policy)
        if(empty($company_ids)){ return [ 'data' => [], 'totals' => [] ]; }

        //Log::debug($company_ids);

        $summary_data = [];

        $col_totals = [
            'sm_total' => 0,
            'nt_total' => 0,
            'wl_total' => 0,
            'mt_total' => 0
        ];

        // Limit by Year
        $date_limit = '';
        if($year && $year != 'Total'){
            $date_limit = "AND YEAR(hc.commissioning_date) = '{$year}'";
        }

        if($mode == 'dist' && ($user->is_admin || $user->is_distributor)){

            $report = DB::query()->fromSub(function ($query) use ($user) {

                $query->from('distributors_companies')
                ->select([
                    'parent_company_id',
                    'company_id'
                ])->groupBy('parent_company_id','company_id');

                $query->union(
                    DB::table('companies')->select([
                        DB::raw('id AS parent_company_id'),
                        DB::raw('id AS company_id')
                    ])->whereNotIn('id', DB::table('distributors_companies')->pluck('company_id'))
                );

            }, 'dc_ids')->select([
                "pc.company_name AS parent_co",
                "cc.company_name AS subsidiary",
                DB::raw("SUM(CASE WHEN hc.node_type='Soil Moisture' $date_limit THEN 1 ELSE 0 END) as sm_count"),
                DB::raw("SUM(CASE WHEN hc.node_type='Nutrients' $date_limit THEN 1 ELSE 0 END) as nutrients_count"),
                DB::raw("SUM(CASE WHEN hc.node_type='Wells' $date_limit THEN 1 ELSE 0 END) as wells_count"),
                DB::raw("SUM(CASE WHEN hc.node_type='Water Meter' $date_limit THEN 1 ELSE 0 END) as meters_count"),
                DB::raw(
                    "(SUM(CASE WHEN hc.node_type='Soil Moisture' $date_limit THEN 1 ELSE 0 END) + " . 
                    " SUM(CASE WHEN hc.node_type='Nutrients' $date_limit THEN 1 ELSE 0 END) + " . 
                    " SUM(CASE WHEN hc.node_type='Wells' $date_limit THEN 1 ELSE 0 END) + " . 
                    " SUM(CASE WHEN hc.node_type='Water Meter' $date_limit THEN 1 ELSE 0 END)" . 
                    ") as line_total"
                )
            ])
            ->join('companies AS pc', 'pc.id', '=', 'dc_ids.parent_company_id')        /* Join in Distributors */
            ->join('companies AS cc', 'cc.id', '=', 'dc_ids.company_id')               /* Join in Subsidiary Company (for Totals) */
            ->join('hardware_config AS hc', 'hc.company_id', '=', 'dc_ids.company_id')
            ->groupByRaw('parent_co, subsidiary WITH ROLLUP');

            // Filtering

            // filter by distributor companies
            // if($user->is_admin || $user->is_distributor){
            //     $report->whereIn('pc.id', $company_ids);
            // } else {
            //     $report->whereIn('cc.id', $company_ids);
            // }

            $report->whereIn('pc.id', $company_ids);

            $report = $report->get()->toArray();

            if($report){

                $prev_parent_co  = null;
                $prev_subsidiary = null;
                $grand_total     = 0;

                foreach($report as $row){

                    $parent_co  = $grand_total && $row->parent_co  == $prev_parent_co  ? '' : $row->parent_co;
                    $subsidiary = $grand_total && $row->subsidiary == $prev_subsidiary ? '' : $row->subsidiary;
                    $sub_total  = $row->sm_count + $row->nutrients_count + $row->wells_count + $row->meters_count;

                    $is_grand_total = empty($row->parent_co) && empty($row->subsidiary);
                    $is_sub_total   = !empty($row->parent_co) && empty($row->subsidiary);

                    $record = [];

                    $record['parent_co']       = $is_sub_total || $is_grand_total ? '' : $parent_co;
                    $record['subsidiary']      = $is_sub_total || $is_grand_total ? '' : $subsidiary;
                    $record['sm_count']        = $is_sub_total || $is_grand_total ? '' : ($row->sm_count        == 0 ? '' : $row->sm_count);
                    $record['nutrients_count'] = $is_sub_total || $is_grand_total ? '' : ($row->nutrients_count == 0 ? '' : $row->nutrients_count);
                    $record['wells_count']     = $is_sub_total || $is_grand_total ? '' : ($row->wells_count     == 0 ? '' : $row->wells_count);
                    $record['meters_count']    = $is_sub_total || $is_grand_total ? '' : ($row->meters_count    == 0 ? '' : $row->meters_count);
                    $record['row_type']        = $is_sub_total ? 'Subtotal' : ($is_grand_total ? 'Total' : 'Data');
                    $record['line_total']      = $is_sub_total ? $sub_total : ($is_grand_total ? $grand_total : $row->line_total);

                    $col_totals['sm_total'] += $row->sm_count;
                    $col_totals['nt_total'] += $row->nutrients_count;
                    $col_totals['wl_total'] += $row->wells_count;
                    $col_totals['mt_total'] += $row->meters_count;

                    $summary_data[] = $record;

                    if(!$is_sub_total && !$is_grand_total){
                        $grand_total += $sub_total;
                    }

                    $prev_parent_co  = $row->parent_co;
                    $prev_subsidiary = $row->subsidiary;
                }
            }
        } else if($mode == 'sub'){

            $report = DB::table('hardware_config AS hc')->select([
                DB::raw("SUM(CASE WHEN node_type='Soil Moisture' $date_limit THEN 1 ELSE 0 END) as sm_count"),
                DB::raw("SUM(CASE WHEN node_type='Nutrients' $date_limit THEN 1 ELSE 0 END) as nutrients_count"),
                DB::raw("SUM(CASE WHEN node_type='Wells' $date_limit THEN 1 ELSE 0 END) as wells_count"),
                DB::raw("SUM(CASE WHEN node_type='Water Meter' $date_limit THEN 1 ELSE 0 END) as meters_count"),
                DB::raw(
                    "(SUM(CASE WHEN node_type='Soil Moisture' $date_limit THEN 1 ELSE 0 END) + " . 
                    " SUM(CASE WHEN node_type='Nutrients' $date_limit THEN 1 ELSE 0 END) + " . 
                    " SUM(CASE WHEN node_type='Wells' $date_limit THEN 1 ELSE 0 END) + " .
                    " SUM(CASE WHEN node_type='Water Meter' $date_limit THEN 1 ELSE 0 END)) as line_total"
                )
            ])->groupBy('company_id')
            ->where('company_id', $company_ids[0])
            ->get()
            ->toArray();

            $grand_total = 0;
            if($report){
                foreach($report as &$row){
                    $grand_total += $row->line_total;

                    $col_totals['sm_total'] += $row->sm_count;
                    $col_totals['nt_total'] += $row->nutrients_count;
                    $col_totals['wl_total'] += $row->wells_count;
                    $col_totals['mt_total'] += $row->meters_count;

                    $row->row_type = 'Data';

                    $summary_data[] = (array) $row;
                }
                $summary_data[] = [
                    'sm_count' => '',
                    'nutrients_count' => '',
                    'wells_count' => '',
                    'meters_count' => '',
                    'line_total' => $grand_total,
                    'row_type' => 'Total'
                ];
            }
        }

        return [ 'data' => $summary_data, 'totals' => $col_totals ];
    }

    public function details(
        $user,
        $year,
        $company_ids,
        $mode
    ){

        // no selection, no data (the best policy)
        if(empty($company_ids)){ return []; }

        if($mode == 'dist' && ($user->is_admin || $user->is_distributor)){

            $report = DB::query()->fromSub(function ($query) use ($user) {

                $query->from('distributors_companies')
                ->select([
                    'parent_company_id',
                    'company_id'
                ])->groupBy('parent_company_id','company_id');

                $query->union(
                    DB::table('companies')->select([
                        DB::raw('id AS parent_company_id'),
                        DB::raw('id AS company_id')
                    ])->whereNotIn('id', DB::table('distributors_companies')->pluck('company_id'))
                );

            }, 'dc')->select([
                DB::raw("pc.company_name AS parent_co"),
                DB::raw("cc.company_name AS subsidiary"),
                "hc.node_type",
                "hwc.node_count",
                "fl.field_name",
                "hc.node_address",
                DB::raw("hc.commissioning_date AS node_est")
            ]);

            // Limit by Year
            if($year && $year != 'Total'){
                $report->whereRaw("YEAR(hc.commissioning_date) = '{$year}'");
            }

            // Essential joins
            $report->join('hardware_config AS hc', 'hc.company_id', '=', 'dc.company_id');
            $report->join('companies AS cc', 'cc.id', '=', 'dc.company_id');
            $report->join('fields AS fl', 'fl.node_id', '=', 'hc.node_address');

            // Join in Distributors
            $report->join('companies AS pc', 'pc.id', '=', 'dc.parent_company_id');

            // Join in Node Count
            $report->joinSub(
                DB::table('hardware_config')
                    ->select([DB::raw('COUNT(node_address) AS node_count'),'company_id', 'node_type'])
                    ->groupBy(['company_id', 'node_type']),
                'hwc',
                function($join){
                    $join->on('dc.company_id', '=', 'hwc.company_id');
                    $join->on('hc.node_type', '=', 'hwc.node_type');
                }
            );

            // filter by distributor companies
            // if($user->is_admin || $user->is_distributor){
            //     $report->whereIn('pc.id', $company_ids);
            // } else {
            //     $report->whereIn('cc.id', $company_ids);
            // }

            $report->whereIn('pc.id', $company_ids);

            // Add in Distributor Ordering
            $report->orderBy('pc.company_name','asc');
            $report->orderBy('cc.company_name','asc');
            $report->orderBy('hc.node_type','asc');
            $report->orderBy('node_est','asc');

            $report = $report->get()->toArray();
            $detail_data = [];

            if($report){

                //Log::debug($report);

                $prev_parent_co  = null;
                $prev_subsidiary = null;
                $prev_node_type  = null;
                $prev_node_count = null;

                $i = 0;

                foreach($report as $row){

                    $parent_co  = $i && $row->parent_co  == $prev_parent_co  ? '' : $row->parent_co;
                    $subsidiary = $i && $row->subsidiary == $prev_subsidiary ? '' : $row->subsidiary;
                    $node_type  = $i && ($row->node_type == $prev_node_type && $row->subsidiary == $prev_subsidiary) ? '' : $row->node_type;
                    $node_count = $i && ($row->node_count == $prev_node_count && $row->subsidiary == $prev_subsidiary) ? '' : $row->node_count;

                    $record = [];

                    $record['parent_co']    = $parent_co;
                    $record['subsidiary']   = $subsidiary;
                    $record['node_type']    = $node_type;
                    $record['node_count']   = $node_count;
                    $record['field_name']   = $row->field_name;
                    $record['node_address'] = $row->node_address;
                    $record['node_est']     = $row->node_est;

                    $detail_data[] = $record;

                    $prev_parent_co  = $row->parent_co;
                    $prev_subsidiary = $row->subsidiary;
                    $prev_node_type  = $row->node_type;
                    $prev_node_count = $row->node_count;

                    $i++;
                }
            }
        } else if($mode == 'sub'){

            $report = DB::table('hardware_config AS hc')->select(
                "hc.node_type",
                "hwc.node_count",
                "fl.field_name",
                "hc.node_address",
                DB::raw("hc.commissioning_date AS node_est")
            );

            // Maybe Limit by Year
            if($year && $year != 'Total'){
                $report->whereRaw("YEAR(hc.commissioning_date) = '{$year}'");
            }

            $report->join('fields AS fl', 'fl.node_id', '=', 'hc.node_address')
            ->joinSub(
                DB::table('hardware_config AS hcc')
                    ->select([DB::raw('COUNT(hcc.node_address) AS node_count'),'hcc.company_id', 'hcc.node_type'])
                    ->groupBy(['hcc.company_id', 'hcc.node_type']),
                'hwc',
                function($join){
                    $join->on('hc.company_id', '=', 'hwc.company_id');
                    $join->on('hc.node_type', '=', 'hwc.node_type');
                }
            )->where('hc.company_id', $company_ids[0]);

            $report->orderBy('hc.node_type','asc')
            ->orderBy('hc.commissioning_date','asc');

            $report = $report->get()->toArray();

            $detail_data = [];

            if($report){

                $prev_node_type  = null;
                $prev_node_count = null;

                $i = 0;

                foreach($report as $row){

                    $node_type  = $i && $row->node_type == $prev_node_type ? '' : $row->node_type;
                    $node_count = $i && ($row->node_count == $prev_node_count && $row->node_type == $prev_node_type) ? '' : $row->node_count;
                    //$node_count = $row->node_count;

                    $record = [
                        'node_type'    => $node_type,
                        'node_count'   => $node_count == 0 ? '' : $node_count,
                        'field_name'   => $row->field_name,
                        'node_address' => $row->node_address,
                        'node_est'     => $row->node_est
                    ];

                    $detail_data[] = $record;

                    $prev_node_type  = $row->node_type;
                    $prev_node_count = $row->node_count;

                    $i++;

                }
            }
        }

        return $detail_data;
    }

    public function html(
        $user,
        $year,
        $company_ids,
        $mode,
        $detailed
    ){
        $summary_data = $this->summary($user, $year, $company_ids, $mode);

        if(empty($summary_data['data'])) { return '<h1>No Data</h1>'; }

        $totals = $summary_data['totals'];

        $detail_data = [];
        if($detailed){
            $detail_data = $this->details($user, $year, $company_ids, $mode);
        }

        // date printed on header, top right
        $report_date = date("Y-m-d");
        $logo = public_path('img/exe/logo_white.png');

        $report_title = "Device Placement";

        if($year !== 'Total'){
            $report_title .= " $year";
        }

        $html = <<<HTML
        <html>
        <head>
            <style>
                @page {
                    margin: 100px 50px;
                }

                html {
                    font-family: 'Helvetica' !important;
                    font-weight: 400;
                    color:#111111;
                }

                #header {
                    position: fixed;
                    left: -50px;
                    right: -50px;
                    top: -100px;
                    height: 50px;
                    background-color: #00A04C;
                    color:#ffffff;
                }

                #footer {
                    position: fixed;
                    left: -50px;
                    right: -50px;
                    bottom: -100px;
                    height: 50px;
                    background-color: #eeeeee;
                }

                #report_logo {
                    position:absolute;
                    top:50%;
                    transform:translateY(-50%);
                    left:50px;
                    width: auto;
                    max-height:40px;
                }

                .header_container,
                .footer_container {
                    position:relative;
                }

                #report_title {
                    position:absolute;
                    bottom:-0.4em;
                    left:50%;
                    transform:translateX(-50%);
                }

                #report_date {
                    position:absolute;
                    bottom:-0.4em;
                    right:0;
                    transform:translateX(-50%);
                }

                #report_table {
                    width: 100%;
                }

                #report_pgnr {
                    position:absolute;
                    right:0;
                    bottom:-0.5em;
                }

                .text-left {
                    text-align: left!important;
                }

                .text-right {
                    text-align: right!important;
                }

                table {
                    border: 0.01em solid #efefef;
                    border-collapse: collapse;
                    margin-bottom:2em;
                    font-size:0.9em;
                }

                table th {
                    background-color: #eeeeee;
                }

                table td,
                table th {
                    border-left: 0;
                    border-right: 0;
                    border-top: 0.01em solid #efefef;
                    border-bottom: 0.01em solid #efefef;
                    padding:0.2em;
                }

                table tr:nth-child(even) {
                    background-color: #efefef;
                }

            </style>
        </head>
        <body>
            <div id="header">
                <div class='header_container'>
                    <img id='report_logo' src='{$logo}'/>
                    <h2  id='report_title'>$report_title</h2>
                    <h3  id='report_date'>$report_date</h3>
                </div>
            </div>
            <div id="footer">
            </div>
        HTML;

        // detailed report
        if($detail_data){

            $html .= "<h3'>Details</h3>";
            $html .= "<table id='report_table'>";
            $html .= '<thead>';
            $html .= '  <tr>';
            if($mode == 'dist' && ($user->is_admin || $user->is_distributor)){
                $html .= '    <th class="text-left">Distributor</th>';
                $html .= '    <th class="text-left">Subsidiary</th>';
            }
            $html .= '    <th class="text-left">Type</th>';
            $html .= '    <th class="text-left">Qty</th>';
            $html .= '    <th class="text-left">Field</th>';
            $html .= '    <th class="text-left">Address</th>';
            $html .= '    <th class="text-right">Node EST.</th>';
            $html .= '  </tr>';
            $html .= '</thead>';
            $html .= '<tbody>';

            foreach($detail_data as $row){

                $html .= '<tr>';
                if($mode == 'dist' && ($user->is_admin || $user->is_distributor)){
                    $html .= '  <td>' . $row['parent_co'] . '</td>';
                    $html .= '  <td>' . $row['subsidiary'] . '</td>';
                }
                $html .= '  <td>' . $row['node_type'] . '</td>';
                $html .= '  <td class="text-left">' . $row['node_count'] . '</td>';
                $html .= '  <td>' . $row['field_name'] . '</td>';
                $html .= '  <td>' . $row['node_address'] . '</td>';
                $html .= '  <td class="text-right">' . $row['node_est'] . '</td>';
                $html .= '</tr>';

            }

            $html .= '</tbody>';
            $html .= "</table>";

        }

        // summary

        if($summary_data['data']){
            if($detailed && $detail_data){
                $html .= "<h3 style='page-break-before: always;'>Summary</h3>";
            } else {
                $html .= "<h3>Summary</h3>";
            }

            $popcols = 0;

            $html .= "<table id='report_table'>";
            $html .= '<thead>';
            $html .= '  <tr>';
            if($mode == 'dist' && ($user->is_admin || $user->is_distributor)){
                $html .= '    <th class="text-left">Distributor</th>';                            // MAYBE
                $html .= '    <th class="text-left">Subsidiary</th>';                             // MAYBE
            }
            $html .= $totals['sm_total'] ? '    <th class="text-right">Soil Moisture</th>' : '';  // MAYBE
            $html .= $totals['nt_total'] ? '    <th class="text-right">Nutrients</th>' : '';      // MAYBE
            $html .= $totals['wl_total'] ? '    <th class="text-right">Well Controls</th>' : '';  // MAYBE
            $html .= $totals['mt_total'] ? '    <th class="text-right">Meters</th>' : '';         // MAYBE

            $html .= '    <th class="text-right">Line Total</th>';                                // ALWAYS
            $html .= '  </tr>';
            $html .= '</thead>';
            $html .= '<tbody>';

            $popcols += $totals['sm_total'] > 0 ? 1 : 0;
            $popcols += $totals['nt_total'] > 0 ? 1 : 0;
            $popcols += $totals['wl_total'] > 0 ? 1 : 0;
            $popcols += $totals['mt_total'] > 0 ? 1 : 0;

            foreach($summary_data['data'] as $row){

                $html .= '<tr>';
                if($mode == 'dist' && ($user->is_admin || $user->is_distributor)){
                    $html .= '  <td>' . $row['parent_co'] . '</td>';
                    $html .= '  <td>' . $row['subsidiary'] . '</td>';
                }

                if($row['row_type'] == 'Data'){

                    if($totals['sm_total']){ $html .= '  <td class="text-right">' . $row['sm_count'] . '</td>'; }
                    if($totals['nt_total']){ $html .= '  <td class="text-right">' . $row['nutrients_count'] . '</td>'; }
                    if($totals['wl_total']){ $html .= '  <td class="text-right">' . $row['wells_count'] . '</td>'; }
                    if($totals['mt_total']){ $html .= '  <td class="text-right">' . $row['meters_count'] . '</td>'; }

                } else if(in_array($row['row_type'], ['Subtotal','Total'])){
                    $html .= '<td class="text-right" colspan="' . $popcols . '">' . $row['row_type'] . '</td>';
                }

                $html .= '<td class="text-right">' . $row['line_total'] . '</td>';

                $html .= '</tr>';

            }
        }

        $html .= '</tbody>';
        $html .= "</table>";

        $html .= '
            <script type="text/php">
                if ( isset($pdf) ) {
                    $w = $pdf->get_width();
                    $h = $pdf->get_height();
                    $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
                    $font = $fontMetrics->get_font("helvetica");
                    $size = 10;
                    $text_height = $fontMetrics->getFontHeight($font, $size);
                    $y = $h - 2.5 * $text_height;
                    $x = $w - 75;
                    $color = array(0,0,0);
                    $pdf->page_text($x, $y, $text, $font, $size, $color);
                }
            </script>
        </body>
        </html>';

        return $html;
    }

}