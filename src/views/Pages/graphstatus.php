<?php
case "ave":
$ave = array();
$index = 0;

$y_max = $field->full > $y_max ? $field->full : $y_max;
$y_min = $field->refill < $y_min ? $field->refill : $y_min;

    $capacity = $field->full - $field->refill;
    $stageCount = count($stages);

    foreach ($node_data as $series) {
    if (!empty($populated[$index])) {
    $dt = new \DateTime($series->date_time);
    $dt->setTimezone(new \DateTimeZone($this->tz));

    $inAnyStage = false;

    $y_max = $series->average > $y_max ? $series->average : $y_max;
    $y_min = $series->average < $y_min ? $series->average : $y_min;

        $xVal = ($dt->getTimestamp() + $dt->getOffset()) * 1000;

        $rec = ['x' => $xVal, 'y' => (float)$series->average, 'status' => ''];

        // Calculate Status per Data Point (if Growth Stages Exist)
        if ($stageCount >= 2) {
        for ($i = 0; $i < $stageCount - 1; $i++) { // falls within growth stage if ($xVal>= $stages[$i]['x'] && $xVal <= $stages[$i + 1]['x']) { $capacity=$stages[$i]['high'] - $stages[$i]['low']; $rec['status']=(float)number_format($capacity ? ((($rec['y'] - $stages[$i]['low']) / $capacity) * 100) : 0, 2, '.' , '' ); $inAnyStage=true; break; } } // falls outside growth stage if (!$inAnyStage) { $capacity=$field->full - $field->refill;
                $rec['status'] = (float)number_format($capacity ? ((($rec['y'] - $field->refill) / $capacity) * 100) : 0, 2, '.', '');
                }
                } else {
                // (float)number_format($x_max, 2, '.', '')
                $rec['status'] = (float)number_format($capacity ? ((($rec['y'] - $field->refill) / $capacity) * 100) : 0, 2, '.', '');
                }

                array_push($ave, $rec);
                }
                $index++;
                }

                $plotOptions['areasplinerange'] = [
                'series' => [
                'pointPlacement' => 'on'
                ],
                'fillColor' => [
                'linearGradient' => [0, 0, 0, 300],
                'stops' => [
                [0, 'rgba(1, 164, 222, 0.5)'],
                [1, 'rgba(1, 164, 222, 0.1)'],
                ]
                ]
                ];

                $series = [['name' => 'Average', 'color' => 'black', 'type' => 'spline', 'data' => $ave]];

                if ($has_cultivars) {
                $series[] = ['name' => 'Stages', 'type' => 'areasplinerange', 'data' => $stages];
                }

                $graph_data = [
                'graph' => array(
                'series' => $series,
                'yAxis' => array(
                'title' => array(
                'text' => 'Average Percentage'
                )
                ),
                'title' => [
                'text' => $field->field_name . ' - Percentage Average - ' . $request->node_address,
                'widthAdjust' => -200
                ],
                'plotOptions' => $plotOptions
                ),
                'x_max' => (float)number_format($x_max, 2, '.', ''),
                'x_min' => (float)number_format($x_min, 2, '.', ''),
                'y_max' => (float)number_format($y_max, 2, '.', '') + 1,
                'y_min' => (float)number_format($y_min, 2, '.', '') - 1,
                'full' => (float)number_format($field->full, 2, '.', ''),
                'refill' => (float)number_format($field->refill, 2, '.', '')
                ];
                break;