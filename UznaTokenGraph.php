<?php




class UznaTokenGraph {



    public static function getSvgFromRequest($request){
        
        ['time' => $time, 'value' => $value, 'tokenId' => $token_id, 'type' => $type] = $request;
        
        if(
            !in_array($value, ['market', 'base']) 
            || 
            !in_array($type, ['usd', 'pair'])
            ||
            !in_array($time, ['1w', '2w', '1m', 'all']) 
        ){
            return false;
        }
        
        $svg = "";


        if($time === 'all'){
            $price_alias = $value.'_price_'.$type;
            $svg = self::TokenAllTime($token_id, $price_alias);
        }else{

            if($value === 'market'){
                // in this case we just return svg column from the table because we do save market value svg graphs in db
                $token = DB::selectOne('SELECT * FROM tokens WHERE id = ?', [$token_id]);
                $svg = $token['svg_'.$type.'_'.$time] ?? null;
            
            }else if($value === 'base'){
                // we have to generate svg on the fly
                $svg = self::tokenBaseData($token_id, $time, $type);

            }

        }
        // var_dump($svg);
        return $svg;
    }



    public static function generateDataSvgHtml($data, $svg_width = 100, $svg_height = 50, $price_alias = 'price', $settings = []){

        if(empty($data)){
            return '';
        }
        $expected_settings = ['homepage'];
        foreach($expected_settings as $expected_setting){
            ${$expected_setting} = $settings[$expected_setting] ?? null;
        }
    

        $show_circles = 0;
        $circle_visualization = '';

        $cnt = count($data);

        $allow_curves_limit = 30;
        $allow_curves = $cnt < $allow_curves_limit ? true : false;
        // dd($data);

        $is_object = is_object($data[0]);



        $first_price = $is_object ? $data[0]->{$price_alias} : $data[0][$price_alias];
        $last_price = $is_object ? $data[$cnt - 1]->{$price_alias} : $data[$cnt - 1][$price_alias];

        $first_date = $is_object ? $data[0]->created_at : $data[0]['created_at'];
        // $last_date = $is_object ? $data[$cnt - 1]->created_at : $data[$cnt - 1]['created_at'];
        $last_date = date('Y-m-d H:i:s');

        $min = $is_object ? $data[0]->{$price_alias} : $data[0][$price_alias];
        $max = $is_object ? $data[0]->{$price_alias} : $data[0][$price_alias];
        for($i = 0; $i < $cnt; $i++){
            $price = $is_object ? $data[$i]->{$price_alias} : $data[$i][$price_alias];
            if($price > $max){
                $max = $price;
            }else if($price < $min){
                $min = $price;
            }
        }

        $data[$cnt] = $is_object 
            ? (object)[$price_alias => $last_price, 'created_at' => date('Y-m-d H:i:s')] 
            : [$price_alias => $last_price, 'created_at' => date('Y-m-d H:i:s')];
        $cnt++;


        $color = $first_price > $last_price ? 'red' : 'green';


        $time_start = strtotime($first_date);
        $time_end = strtotime($last_date);


        $height_proportion = $max / $svg_height;
        $price_interval = $max - $min;
        $time_interval = $time_end - $time_start;

        $acceptable_curve = $svg_width / 20; //20, 22   

        $previous_x = null;
        $previous_y = null;
        $path = 'M ';


        $json = [];
        for($i = 0; $i < $cnt; $i++){
            $unit = $data[$i];
            // dd($cnt, $data);
            // dd($unit->market_price_usd);
            $price = $is_object ? $unit->{$price_alias} : $unit[$price_alias];
            $created_at = $is_object ? $unit->created_at : $unit['created_at'];
                

            $json[] = [$created_at, $price];

            $y = $svg_height - (($price - $min)/$price_interval)*$svg_height;
            $x = ((strtotime($created_at) - $time_start) / $time_interval) * $svg_width;

            if(!isset($previous_y)){
                $previous_y = $y;
            }
            if(!isset($previous_x)){
                $previous_x = $x;
            }

            $curve = '';
            if($allow_curves){
                $diff_x = $x - $previous_x;
                $diff_y = $y - $previous_y;
                if($diff_x > $acceptable_curve){
                    $control_point_x = $x - $acceptable_curve;
                    $starting_x = $control_point_x;
                    $curve = ' L '.$control_point_x.' '.$previous_y;
                    $curve_x_distance = $acceptable_curve;
                }else{
                    $curve_x_distance = $x - $previous_x; 
                    $starting_x = $previous_x;
                }			

                $x_ratio = $curve_x_distance / 9; // since we need 8 equally arranged circles in curve

                $curve_point_1_rod_1_x = $starting_x + $x_ratio * 1;

                if($diff_y > 0){

                    $curve_point_1_rod_1_x = $starting_x + $x_ratio * 1;
                    $curve_point_1_rod_1_y = $previous_y;

                    $curve_point_1_rod_2_x = $starting_x + $x_ratio * 2;
                    $curve_point_1_rod_2_y = $previous_y;

                    $curve_point_1_x = $starting_x + $x_ratio * 3;
                    $curve_point_1_y = $previous_y + $diff_y * 0.16;


                            

                    $curve_point_2_rod_1_x = $starting_x +  $x_ratio * 4;
                    $curve_point_2_rod_1_y = $previous_y + $diff_y * 0.32;

                    $curve_point_2_rod_2_x = $starting_x + $x_ratio * 5;
                    $curve_point_2_rod_2_y = $previous_y + $diff_y * 0.65;

                    $curve_point_2_x = $starting_x + $x_ratio * 6;
                    $curve_point_2_y = $previous_y + $diff_y * 0.81;

                        


                }else{
                    $diff_y = abs($diff_y);
                    $curve_point_1_rod_1_x = $starting_x + $x_ratio * 1;
                    $curve_point_1_rod_1_y = $previous_y;

                    $curve_point_1_rod_2_x = $starting_x + $x_ratio * 2;
                    $curve_point_1_rod_2_y = $previous_y;

                    $curve_point_1_x = $starting_x + $x_ratio * 3;
                    $curve_point_1_y = $previous_y - $diff_y * 0.16;

                    


                    $curve_point_2_rod_1_x = $starting_x +  $x_ratio * 4;
                    $curve_point_2_rod_1_y = $previous_y - $diff_y * 0.32;

                    $curve_point_2_rod_2_x = $starting_x + $x_ratio * 5;
                    $curve_point_2_rod_2_y = $previous_y - $diff_y * 0.65;

                    $curve_point_2_x = $starting_x + $x_ratio * 6;
                    $curve_point_2_y = $previous_y - $diff_y * 0.81;

                }


                $curve .= ' C '
                .$curve_point_1_rod_1_x.' '.$curve_point_1_rod_1_y.' '
                .$curve_point_1_rod_2_x.' '.$curve_point_1_rod_2_y.' '
                .$curve_point_1_x.' '.$curve_point_1_y.' '.

                ' C '.$curve_point_2_rod_1_x.' '.$curve_point_2_rod_1_y.' '
                .$curve_point_2_rod_2_x.' '.$curve_point_2_rod_2_y.' '
                .$curve_point_2_x.' '.$curve_point_2_y.' ';

                $endpoint_rod_1_x = $starting_x + $x_ratio * 7;
                $endpoint_rod_2_x = $starting_x + $x_ratio * 8;
                $endpoint_rods = $endpoint_rod_1_x.' '.$y.' '.$endpoint_rod_2_x.' '.$y;
            }

            $rad = 0.3;
            $transform = ''; //transform="translate(0.5, 0.5)"
            if($show_circles){
                $circle_visualization .= (isset($control_point_x) ? '<circle class="has-tooltip" aria-label="curve_point_1_rod_1: '.$price.'" cx="'.$control_point_x.'" cy="'.$previous_y.'" r="'.$rad.'" fill="#fff" fill-opacity="1" '.$transform.'></circle>' : '').'
                <circle class="has-tooltip" aria-label="curve_point_1_rod_1: '.$price.'" cx="'.$curve_point_1_rod_1_x.'" cy="'.$curve_point_1_rod_1_y.'" r="'.$rad.'" fill="orange" fill-opacity="1" '.$transform.'></circle>
                <circle class="has-tooltip" aria-label="curve_point_1_rod_2: '.$price.'" cx="'.$curve_point_1_rod_2_x.'" cy="'.$curve_point_1_rod_2_y.'" r="'.$rad.'" fill="crimson" fill-opacity="1" '.$transform.'></circle>
                <circle class="has-tooltip" aria-label="curve_point_1: '.$price.'" cx="'.$curve_point_1_x.'" cy="'.$curve_point_1_y.'" r="'.$rad.'" fill="#fff" fill-opacity="1" '.$transform.'></circle>

                <circle class="has-tooltip" aria-label="curve_point_2_rod_1: '.$price.'" cx="'.$curve_point_2_rod_1_x.'" cy="'.$curve_point_2_rod_1_y.'" r="'.$rad.'" fill="orange" fill-opacity="1" '.$transform.'></circle>
                <circle class="has-tooltip" aria-label="curve_point_2_rod_2: '.$price.'" cx="'.$curve_point_2_rod_2_x.'" cy="'.$curve_point_2_rod_2_y.'" r="'.$rad.'" fill="crimson" fill-opacity="1" '.$transform.'></circle>
                <circle class="has-tooltip" aria-label="curve_point_2: '.$price.'" cx="'.$curve_point_2_x.'" cy="'.$curve_point_2_y.'" r="'.$rad.'" fill="#fff" fill-opacity="1" '.$transform.'></circle>

                <circle class="has-tooltip" aria-label="endpoint_rod_1: '.$price.'" cx="'.$endpoint_rod_1_x.'" cy="'.$y.'" r="'.$rad.'" fill="orange" fill-opacity="1" '.$transform.'></circle>
                <circle class="has-tooltip" aria-label="endpoint_rod_2: '.$price.'" cx="'.$endpoint_rod_2_x.'" cy="'.$y.'" r="'.$rad.'" fill="crimson" fill-opacity="1" '.$transform.'></circle>
                <circle class="has-tooltip" aria-label="x: '.$price.'" cx="'.$x.'" cy="'.$y.'" r="'.$rad.'" fill="#fff" fill-opacity="1" '.$transform.'></circle>
                ';
            }
                
            
            // $curve = ''; //
            // if($allow_curves){

            // }
            $path .= ($i > 0 && $allow_curves ? $curve.($endpoint_rod_1_x ? ' C '.$endpoint_rods.' ' : ' L '): '').$x.' '.$y.' ';

            $previous_x = $x;
            $previous_y = $y;
        }


        // var_dump($path);
        return $homepage 
        
        ? [
            'homepage' => '<svg data-min="'.$min.'" data-max="'.$max.'" width="100%" height="100%" viewBox="0 0 '.$svg_width.' '.$svg_height.'" overflow="visible">
            <path d="'.$path.'" stroke="'.$color.'" fill="transparent" stroke-width="0.5" '.$transform.'/>

            '.($show_circles ? $circle_visualization: '').'
        </svg>',


            'json' => '<svg data-min="'.$min.'" data-max="'.$max.'" data-json=\''.json_encode($json).'\' width="100%" height="100%" viewBox="0 0 '.$svg_width.' '.$svg_height.'" overflow="visible">
            <path d="'.$path.'" stroke="'.$color.'" fill="transparent" stroke-width="0.3" '.$transform.'/>

            '.($show_circles ? $circle_visualization: '').'
        </svg>'
        ]
        
        : '<svg data-min="'.$min.'" data-max="'.$max.'" data-json=\''.json_encode($json).'\' width="100%" height="100%" viewBox="0 0 '.$svg_width.' '.$svg_height.'" overflow="visible">
            <path d="'.$path.'" stroke="'.$color.'" fill="transparent" stroke-width="0.3" '.$transform.'/>

            '.($show_circles ? $circle_visualization: '').'
        </svg>';
    }










    public static function getTokenSvgs($tokens){
		$homepage_time = 7;
		$month_time = 28;
		
        $svg_renderer = [];

        // dd($tokens);
        foreach($tokens as $token){
			
            $token = (array)$token;
            $arr_1w = DB::select(self::getTokenSvgsQuery($token['id'], $homepage_time));
            $cnt_1w = count($arr_1w);

            $arr_1m = DB::select(self::getTokenSvgsQuery($token['id'], $month_time));
            $cnt_1m = count($arr_1m);
            
            $month_result = [];
            $dot_limit = 300;
            if($cnt_1m > $dot_limit){
                $division = ceil($cnt_1m / $dot_limit);
                // $reminder = $cnt_1m % $dot_limit;
                $i = 0;
                foreach($arr_1m as $month_value) {
                    if ($i++ % $division == 0) {
                        $month_result[] = $month_value;
                    }
                }
            }else{
                $month_result = $arr_1m;
            }
            // dd($month_result);
            // dd($arr_1m);
            
            
            

            $svg_renderer[$token['id']] = [
            
                'usd_1w' => self::generateDataSvgHtml($arr_1w, 100, 50, 'market_price_usd', ['homepage' => true]),
                'pair_1w' => self::generateDataSvgHtml($arr_1w, 100, 50, 'market_price_pair'),
                
                'usd_1m' => self::generateDataSvgHtml($month_result, 100, 50, 'market_price_usd'),
                'pair_1m' => self::generateDataSvgHtml($month_result, 100, 50, 'market_price_pair'),
                
                'market_price' => self::generateDataSvgHtml($arr_1m, 300, 150, 'market_price_pair'),
                'baseline_value' => self::generateDataSvgHtml($arr_1m, 300, 40, 'base_price_pair'),

                // 'usd_1m_homepage' => generateDataSvgHtml($arr_1m, 300, 40, 'market_price_usd'),
                // 'pair_1m_homepage' => generateDataSvgHtml($arr_1m, 300, 40, 'market_price_pair'),
            ];
            // dd($svg_renderer);

        }
        return $svg_renderer;
    }
    
    public static function getTokenSvgsQuery($token_id, $unit_count, $units = 'days') {
        
        $time_condition = $units == 'hours' 
            ? ' created_at > DATEADD(HOUR, -'.$unit_count.', GETDATE())' 
            : ' DATE(created_at) >= CURDATE() - INTERVAL '.$unit_count.' DAY;';

        $query = "	SELECT 
                        market_price_usd, 
                        market_price_pair,
                        base_price_pair,
                        created_at
                    FROM sdt_prices_unique
                    WHERE token_id = ".$token_id." AND 
                    ".$time_condition;
        return $query;
    }


    public static function job(){
        
        $items = DB::select("SELECT * FROM tokens");

        $tokenSVGs = self::getTokenSvgs($items);
        // dd($tokenSVGs);

        foreach($items as $item) {
            $item = (array)$item;
            
            // if($item['id'] != 1){
            //     return;
            // }
            // Updates price and [price change 24h]
            DB::update(
			    "UPDATE tokens SET
			        svg_usd_1w = ?,
			        svg_pair_1w = ?,
			        svg_usd_1m = ?,
			        svg_pair_1m = ?,
			        svg_homepage = ?
			     WHERE id = ?",
			    [
			        $tokenSVGs[$item['id']]['usd_1w']['json'] ?? '',
			        $tokenSVGs[$item['id']]['pair_1w'] ?? '',
			        $tokenSVGs[$item['id']]['usd_1m'],
			        $tokenSVGs[$item['id']]['pair_1m'],
			        $tokenSVGs[$item['id']]['usd_1w']['homepage'] ?? '',
			        $item['id'],
			    ]
			);
            // dd($item, $tokenSVGs);
        }
    }

    public static function TokenAllTime($token_id, $price_alias = 'market_price_usd'){

        $dot_limit = 250;
        $cache_duration = 60;

        $svg = self::cache('token.all_time.'.$token_id.'.'.$price_alias, $cache_duration, function() use($token_id, $price_alias, $dot_limit){

            $cnt_prices = DB::selectOne("SELECT COUNT(*) as count FROM sdt_prices_unique WHERE token_id = ?", [$token_id])['count'];

            
            if($cnt_prices > $dot_limit){
                $limit_interval = ceil($cnt_prices / $dot_limit);

                $data = DB::select("SELECT $price_alias, created_at
                FROM (
                    SELECT @row := @row +1 AS rownum, sdt_prices_unique.*
                    FROM (SELECT @row :=0) r, sdt_prices_unique 
                        WHERE token_id = :token_id 
                            AND $price_alias!=0
                        ORDER BY created_at
                    ) ranked
                WHERE rownum % $limit_interval = 1", ['token_id' => $token_id]);
            }else{

				$data = DB::select("SELECT * FROM sdt_prices_unique
										WHERE token_id = ?
										ORDER BY created_at;", [$token_id]);
            }

            return self::generateDataSvgHtml($data, 100, 50, $price_alias);
            
        });

       return $svg;
        // dd('what', $svg);
    }

    public static function tokenBaseData($token_id, $time = '1w', $type = 'pair'){

        $dot_limit = 250;
        $cache_duration = 60;

        
        // dd($time);
        switch($time){
            case '1w':
                $unit_count = 7;
            break;
            case '2w':
                $unit_count = 14;
            break;
            case '1m':
                $unit_count = 30;
            break;
        }

        if(strtolower($type) == 'usd'){
            $price_alias = 'base_price_usd';
        }else if(strtolower($type) == 'pair'){
            $price_alias = 'base_price_pair';
        }

        $svg = self::cache('token.base.'.$token_id.'.'.$time.'-'.$type, $cache_duration, function() use($token_id, $price_alias, $dot_limit, $unit_count){

            $cnt_prices = DB::selectOne("SELECT COUNT(*) as count FROM sdt_prices_unique WHERE token_id = ?", [$token_id])['count'];
            
            if($cnt_prices > $dot_limit){
                $limit_interval = ceil($cnt_prices / $dot_limit);

                $data = DB::select("SELECT $price_alias, created_at
                FROM (
                    SELECT @row := @row +1 AS rownum, sdt_prices_unique.*
                    FROM (SELECT @row :=0) r, sdt_prices_unique 
                        WHERE token_id = :token_id 
                            AND $price_alias!=0
                        ORDER BY created_at
                    ) ranked
                WHERE rownum % $limit_interval = 1
                AND DATE(created_at) >= CURDATE() - INTERVAL ".$unit_count." DAY;
                ", ['token_id' => $token_id]);
            }else{
                $data = DB::select("SELECT $price_alias, created_at
                FROM sdt_prices_unique
                WHERE token_id = :token_id 
                    AND DATE(created_at) >= CURDATE() - INTERVAL ".$unit_count." DAY;
                ", ['token_id' => $token_id]);
            }

            return self::generateDataSvgHtml($data, 100, 50, $price_alias);
            
        });

       return $svg;
        // dd('what', $svg);
    }





    public static function cache($key, $durationSeconds, $callback){

        $cache_table = 'token_cache';
        
        $cache = DB::selectOne('SELECT * FROM '.$cache_table.' WHERE `key` = ?', [$key]);

        $expiration = time() + $durationSeconds;

        $value = null;

        if(!$cache){
            $value = $callback();

            DB::raw("INSERT INTO $cache_table (`key`, `value`, expiration) VALUES (?,?,?)", [$key, $value, $expiration]);
        }else{

            if($cache['expiration'] <= time()){
                $value = $callback();
                DB::update("UPDATE $cache_table SET value = ?, expiration = ? WHERE `key` = ?", [$value, $expiration, $key]);
            }else{
                $value = $cache['value'];
            }

        }

        return $value;
    }

    
}
