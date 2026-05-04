<?php declare(strict_types = 0);

    $cols_count = 1;

    $header = [
        (new CColHeader('Device'))->addClass(ZBX_STYLE_CENTER),
    ];
    if ($data['show_status']) {
        $header[] = (new CColHeader('Status'))->addClass(ZBX_STYLE_CENTER);
        $cols_count++;
    }
    if ($data['show_value']) {
        $header[] = (new CColHeader('Value'))->addClass(ZBX_STYLE_CENTER);
        $cols_count++;
    }
    if ($data['show_item']) {
        $header[] = (new CColHeader('Item'))->addClass(ZBX_STYLE_CENTER);
        $cols_count++;
    }
    $header[] = (new CColHeader('Graph'))->addClass(ZBX_STYLE_CENTER);
    $table = (new CTableInfo())->setHeader($header);

if (!empty($data['items_data'])) {
    foreach ($data['items_data'] as $itemid => $item) {
        $is_up = (float)$item['lastvalue'] > 0;
        $id_key = (string)$itemid;
        $item_config = $data['custom_labels'][$id_key] ?? null;
        
        $custom_name = '';
        if (is_array($item_config)) {
            $custom_name = $item_config['name'] ?? '';
        } elseif (is_string($item_config)) {
            $custom_name = $item_config;
        }

        $is_binary_item = (is_array($item_config) && isset($item_config['binary'])) 
            ? (bool)$item_config['binary'] 
            : true;

        $display_name = ($custom_name !== '') ? $custom_name : ($item['hosts'][0]['name'] ?? 'Unknown');

        $status_span = (new CSpan())
            ->addClass(ZBX_STYLE_TAG)
            ->addClass($is_up ? ZBX_STYLE_GREEN_BG : ZBX_STYLE_RED_BG)
            ->setTitle($is_up ? 'AVAILABLE' : 'UNAVAILABLE')
            ->setAttribute('style', 'border-radius: 50%; width: 10px; height: 10px; padding: 0; display: inline-block;');

        $sparkline = null;
        if (!empty($data['items_data'])) {
            $history = $data['history_data'][$itemid];
            $values = array_column($history, 'value');
            $clocks = array_column($history, 'clock');
            
            $width = 250;
            $height = 30;
            $count = count($values);
            $step = ($count > 1) ? $width / ($count - 1) : 0;
            
            $padding = 2;
            $svg_elements = [];
            
            $color_green = '#34af67';
            $color_red = '#e45959';
            $color_grey = 'rgba(171, 168, 168, 0.3)';
            $color_blue = '#4794eb';

            if ($is_binary_item) {
                $y_up = 2;
                $y_down = $height - 2;

                $path_green = "";
                $path_red = "";
                $path_grey = "";
                $path_fill = "";

                for ($i = 0; $i < $count - 1; $i++) {
                    $v1 = (float)$values[$i];
                    $v2 = (float)$values[$i+1];
                    $x1 = round($i * $step, 3);
                    $x2 = round(($i + 1) * $step, 3);
                    $y1 = ($v1 > 0) ? $y_up : $y_down;
                    $y2 = ($v2 > 0) ? $y_up : $y_down;

                    if ($v1 > 0) {
                        $path_green .= "M{$x1},{$y1} L{$x2},{$y1} ";
                        $path_fill .= "M{$x1},{$height} L{$x1},{$y1} L{$x2},{$y1} L{$x2},{$height} Z ";
                    } else {
                        $path_red .= "M{$x1},{$y1} L{$x2},{$y1} ";
                    }

                    if ($y1 !== $y2) {
                        $path_grey .= "M{$x2},{$y1} L{$x2},{$y2} ";
                    }
                }

                if ($path_fill !== "") {
                    $svg_elements[] = (new CTag('path', true))
                        ->setAttribute('d', $path_fill)
                        ->setAttribute('style', "fill: rgba(52, 175, 103, 0.15); stroke: none;");
                }

                if ($path_grey !== "") {
                    $svg_elements[] = (new CTag('path', true))
                        ->setAttribute('d', $path_grey)
                        ->setAttribute('style', "stroke: {$color_grey}; stroke-width: 1; fill: none;");
                }

                if ($path_red !== "") {
                    $svg_elements[] = (new CTag('path', true))
                        ->setAttribute('d', $path_red)
                        ->setAttribute('style', "stroke: {$color_red}; stroke-width: 2; fill: none; shape-rendering: crispEdges;");
                }

                if ($path_green !== "") {
                    $svg_elements[] = (new CTag('path', true))
                        ->setAttribute('d', $path_green)
                        ->setAttribute('style', "stroke: {$color_green}; stroke-width: 2; fill: none; shape-rendering: crispEdges;");
                }
            } else {
                $min_val = min($values);
                $real_max = max($values);
                $diff = $real_max - $min_val;
                $units = $item['units'];
                $multiplier = 0.2;
                if ($units === '%') {
                    $multiplier = 1.5; 
                } elseif ($units === '°C' || $units === '°') {
                    $multiplier = 5;
                } elseif ($diff > 1000) {
                    $multiplier = 0.1;
                }
                if ($diff == 0) {
                    $max_val = ($real_max == 0) ? 1 : $real_max * 1.2;
                } else {
                    $max_val = $real_max + ($diff * $multiplier);
                }
                $range = $max_val - $min_val;
                if ($range <= 0) $range = 1;

                $points = [];
                for ($i = 0; $i < $count; $i++) {
                    $x = round($i * $step, 3);
                    $y = round($height - $padding - (($values[$i] - $min_val) / $range) * ($height - 2 * $padding), 3);
                    $points[] = ['x' => $x, 'y' => $y];
                }

                $path_line = "M" . $points[0]['x'] . "," . $points[0]['y'];
                for ($i = 0; $i < count($points) - 1; $i++) {
                    $xc = ($points[$i]['x'] + $points[$i+1]['x']) / 2;
                    $yc = ($points[$i]['y'] + $points[$i+1]['y']) / 2;
                    $path_line .= " Q " . $points[$i]['x'] . "," . $points[$i]['y'] . " " . $xc . "," . $yc;
                }
                $path_line .= " L " . end($points)['x'] . "," . end($points)['y'];
                $path_area = $path_line . " L{$width},{$height} L0,{$height} Z";
                $svg_elements[] = (new CTag('path', true))->setAttribute('d', $path_area)->setAttribute('style', "fill: rgba(71, 148, 235, 0.1); stroke: none;");
                $svg_elements[] = (new CTag('path', true))
                    ->setAttribute('d', $path_line)
                    ->setAttribute('style', "stroke: {$color_blue}; stroke-width: 1.5; fill: none; stroke-linejoin: round; stroke-linecap: round;");
            }

            $svg_elements[] = (new CTag('line', true))
                ->addClass('sparkline-crosshair')
                ->setAttribute('x1', 0)->setAttribute('y1', 0)
                ->setAttribute('x2', 0)->setAttribute('y2', $height)
                ->setAttribute('style', "stroke: #ffffff; stroke-width: 1; display: none; pointer-events: none;");

            $sparkline = (new CTag('svg', true))
                ->addClass('sparkline-svg')
                ->setAttribute('width', '100%')
                ->setAttribute('height', $height)
                ->setAttribute('viewBox', "0 0 $width $height")
                ->setAttribute('data-is-binary', $is_binary_item ? '1' : '0')
                ->setAttribute('data-units', $item['units'])
                ->setAttribute('preserveAspectRatio', 'none')
                ->setAttribute('data-clocks', json_encode($clocks))
                ->setAttribute('data-values', json_encode($values)) 
                ->setAttribute('data-step', $step)
                ->setAttribute('style', 'display: inline-block; vertical-align: middle; overflow: visible; cursor: default;')
                ->addItem($svg_elements);

        } else {
            $sparkline = (new CSpan('No data'))->addClass(ZBX_STYLE_GREY);
        }
        $row = [];
        $name_col = (new CCol($display_name))
            ->addClass(ZBX_STYLE_CENTER)
            ->setAttribute('style', 'vertical-align: middle; transition: all 0.3s;');

        if (!$is_up) {
            $style_down = 'vertical-align: middle; '
                . 'background-color: rgba(228, 89, 89, 0.25); ' 
                . 'color: #ffffff; ' 
                . 'font-weight: bold; '
                . 'box-shadow: inset 3px 0 0 #e45959;';
            $name_col->setAttribute('style', $style_down);
        }

        $row[] = $name_col;

        if ($data['show_status']) {
            $row[] = (new CCol($status_span))
                ->addClass(ZBX_STYLE_CENTER)
                ->setAttribute('style', 'vertical-align: middle;');
        }
        if ($data['show_item']) {
            $row[] = (new CCol($item['name']))
                ->addClass(ZBX_STYLE_CENTER)
                ->setAttribute('style', 'vertical-align: middle;');
        }

        if ($data['show_value']) {
            $row[] = (new CCol($item['lastvalue'] . ' ' . $item['units']))
                ->addClass(ZBX_STYLE_CENTER)
                ->setAttribute('style', 'vertical-align: middle;');
        }

        $row[] = (new CCol($sparkline))
            ->addClass(ZBX_STYLE_CENTER)
            ->setAttribute('style', 'vertical-align: middle;');

        $table->addRow($row);
    }
    
    $ts_start = (int)$data['from_ts'];
    $ts_end   = (int)$data['to_ts'];
    $different_time = $ts_end - $ts_start;
    $step = (int)($different_time / 4);

    $timestamps = [
        $ts_start,
        (int)($ts_start + $step),
        (int)($ts_start + 2 * $step),
        (int)($ts_start + 3 * $step),
        $ts_end
    ];

    $is_same_day = date('d.m.Y', $ts_start) === date('d.m.Y', $ts_end);
    $format = $is_same_day ? 'H:i' : 'd.m H:i';

    $time_axis_div = (new CDiv())
        ->setAttribute('style', 'display: flex; justify-content: space-between; padding: 0 2px;');
    foreach ($timestamps as $ts) {
        $time_axis_div->addItem(
            (new CSpan(date($format, $ts)))
                ->addClass(ZBX_STYLE_GREY)
                ->setAttribute('style', 'font-size: 10px;')
        );
    }
    $empty_col = (new CCol())
        ->setColSpan($cols_count)
        ->setAttribute('style', 'border: none; background: transparent;');
    $graph_col = (new CCol($time_axis_div))
        ->setAttribute('style', 'border: 0; padding-top: 1px;');
    $table->addRow([$empty_col, $graph_col]);

} else {
    $table->setNoDataMessage('Select items in widget configuration');
}

(new CWidgetView($data))->addItem($table)->show();
