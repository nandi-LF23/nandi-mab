<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\nutri_data;
use App\User;
use DB;
use Carbon\carbon;

class DataExportController extends Controller
{
    public function export_csv_nutri(Request $request)
    {
        // SM-M0, Temp-M1, EC-M2, N1-M3, N2-M4, N3-M5, N4-M6
        $output_array = array();
        $row_builder = array();
        //todo headings
        array_push($row_builder, 'id');
        array_push($row_builder, 'DateTime');

        if ($request->export_data['SM']) {
            array_push($row_builder, 'M0_1');
            array_push($row_builder, 'M0_2');
            array_push($row_builder, 'M0_3');
            array_push($row_builder, 'M0_4');
        }
        if ($request->export_data['TEMP']) {
            array_push($row_builder, 'M1_1');
            array_push($row_builder, 'M1_2');
            array_push($row_builder, 'M1_3');
            array_push($row_builder, 'M1_4');
        }
        if ($request->export_data['EC']) {
            array_push($row_builder, 'M2_1');
            array_push($row_builder, 'M2_2');
            array_push($row_builder, 'M2_3');
            array_push($row_builder, 'M2_4');
        }
        if ($request->export_data['N1']) {
            array_push($row_builder, 'M3_1');
            array_push($row_builder, 'M3_2');
            array_push($row_builder, 'M3_3');
            array_push($row_builder, 'M3_4');
        }
        if ($request->export_data['N2']) {
            array_push($row_builder, 'M4_1');
            array_push($row_builder, 'M4_2');
            array_push($row_builder, 'M4_3');
            array_push($row_builder, 'M4_4');
        }
        if ($request->export_data['N3']) {
            array_push($row_builder, 'M5_1');
            array_push($row_builder, 'M5_2');
            array_push($row_builder, 'M5_3');
            array_push($row_builder, 'M5_4');
        }
        if ($request->export_data['N4']) {
            array_push($row_builder, 'M6_1');
            array_push($row_builder, 'M6_2');
            array_push($row_builder, 'M6_3');
            array_push($row_builder, 'M6_4');
        }

        $output_array[] = $row_builder;

        // log::debug($output_array);
        //  die;

        $data = json_decode(nutri_data::select('*')
            ->where('node_address', $request->nutriprobe)
            ->whereDate('date_sampled', '>=', Carbon::today()->subDays($request->subdays))
            ->get());

        $counter = 0;

        foreach ($data as $row) {
            $counter++;
            $row_builder = array();

            array_push($row_builder, $counter);
            array_push($row_builder, $row->date_sampled);

            if ($request->export_data['SM']) {
                array_push($row_builder, $row->M0_1);
                array_push($row_builder, $row->M0_2);
                array_push($row_builder, $row->M0_3);
                array_push($row_builder, $row->M0_4);
            }
            if ($request->export_data['TEMP']) {
                array_push($row_builder, $row->M1_1);
                array_push($row_builder, $row->M1_2);
                array_push($row_builder, $row->M1_3);
                array_push($row_builder, $row->M1_4);
            }
            if ($request->export_data['EC']) {
                array_push($row_builder, $row->M2_1);
                array_push($row_builder, $row->M2_2);
                array_push($row_builder, $row->M2_3);
                array_push($row_builder, $row->M2_4);
            }
            if ($request->export_data['N1']) {
                array_push($row_builder, $row->M3_1);
                array_push($row_builder, $row->M3_2);
                array_push($row_builder, $row->M3_3);
                array_push($row_builder, $row->M3_4);
            }
            if ($request->export_data['N2']) {
                array_push($row_builder, $row->M4_1);
                array_push($row_builder, $row->M4_2);
                array_push($row_builder, $row->M4_3);
                array_push($row_builder, $row->M4_4);
            }
            if ($request->export_data['N3']) {
                array_push($row_builder, $row->M5_1);
                array_push($row_builder, $row->M5_2);
                array_push($row_builder, $row->M5_3);
                array_push($row_builder, $row->M5_4);
            }
            if ($request->export_data['N4']) {
                array_push($row_builder, $row->M6_1);
                array_push($row_builder, $row->M6_2);
                array_push($row_builder, $row->M6_3);
                array_push($row_builder, $row->M6_4);
            }

            $output_array[] = $row_builder;
        }

        //  return response()->json($output_array, 200);

        $fileName = $request->nutriprobe . '.csv';
        // log::debug($output_array);

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $callback = function () use ($output_array) {
            $file = fopen('php://output', 'w');

            foreach ($output_array as $line) {
                fputcsv($file, $line, ',');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
