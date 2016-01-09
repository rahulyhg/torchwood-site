<?php

/**
 * Data queries functionalities for Live Weather Station plugin
 *
 * @since      1.0.0
 * @package    Live_Weather_Station
 * @subpackage Live_Weather_Station/includes
 * @author     Pierre Lannoy <https://pierre.lannoy.fr/>
 */

require_once(LWS_INCLUDES_DIR.'trait-datas-storage.php');

trait Datas_Query {
    
    use Datas_Storage;

    private $dont_filter = array('temperature_max', 'temperature_min', 'temperature_trend', 'pressure_trend', 'loc_latitude',
                                 'loc_longitude', 'loc_altitude', 'windstrength_day_max', 'windangle_hour_max', 'windangle_day_max');

    /**
     * Filter data.
     *
     * @param   array   $data   The data to filter.
     * @return  array   An array containing the filtered data.
     * @since    2.0.0
     * @access   protected
     */
    private function obsolescence_filtering($data) {
        $time = 0;
        $time_owm = 0;
        switch (get_option('live_weather_station_settings')[6]) {
            case 1 :
                $time = 30 * 60;
                $time_owm = floor(2 * 60 * 60);
                break;
            case 2 :
                $time = 60 * 60;
                $time_owm = floor(2 * 60 * 60);
                break;
            case 3 :
                $time = 2 * 60 * 60;
                $time_owm = $time;
                break;
            case 4 :
                $time = 4 * 60 * 60;
                $time_owm = $time;
                break;
            case 5 :
                $time = 12 * 60 * 60;
                $time_owm = $time;
                break;
            case 6 :
                $time = 24 * 60 * 60;
                $time_owm = $time;
                break;
        }
        $time_filter = time() - $time;
        $time_filter_owm = time() - $time_owm;
        if ($time == 0) {
            $result = $data;
        }
        else {
            $result = array();
            foreach ($data as $line) {
                if (in_array($line['measure_type'], $this->dont_filter)) {
                    $result[] = $line;
                }
                elseif ($line['module_type'] == 'NACurrent') {
                    if (strtotime($line['measure_timestamp']) > $time_filter_owm) {
                        $result[] = $line;
                    }
                }
                elseif (strtotime($line['measure_timestamp']) > $time_filter) {
                    $result[] = $line;
                }
            }
        }
        return $result;
    }

    /**
     * Get stations list.
     *
     * @return  array   An array containing the available stations.
     * @since    1.0.0
     * @access   protected
     */
    protected function get_stations_list() {
        global $wpdb;
        $table_name = $wpdb->prefix.self::live_weather_station_datas_table();
        $sql = "SELECT DISTINCT device_id, device_name FROM ".$table_name ;
        try {
            $query = (array)$wpdb->get_results($sql);
            $query_a = (array)$query;
            $result = array();
            foreach ($query_a as $val) {
                $result[] = (array)$val;
            }
            return $result;
        }
        catch(Exception $ex) {
            return array('device_name' => __(LWS_PLUGIN_NAME, 'live-weather-station').' '.__('is not running...', 'live-weather-station'), 'device_id' => 'N/A') ;
        }
    }

    /**
     * Get OpenWeatherMap stations list.
     *
     * @return  array   An array containing the available stations.
     * @since    2.0.0
     */
    protected function get_owm_stations_list() {
        global $wpdb;
        $table_name = $wpdb->prefix.self::live_weather_station_owm_stations_table();
        $sql = "SELECT * FROM ".$table_name ;
        try {
            $query = (array)$wpdb->get_results($sql);
            $query_a = (array)$query;
            $result = array();
            foreach ($query_a as $val) {
                $result[] = (array)$val;
            }
            return $result;
        }
        catch(Exception $ex) {
            return array() ;
        }
    }

    /**
     * Get an OpenWeatherMap station.
     *
     * @param   integer $station_id     Optional. The station id.
     * @return  array   An array containing the station details.
     * @since    2.0.0
     */
    protected function get_owm_station($station_id=0) {
        if ($station_id == 0) {
            $ccs = '';
            $cc = explode ('_', get_locale());
            if (count($cc) > 1) {
                $ccs = strtoupper($cc[1][0].$cc[1][1]);
            }
            $nothing = array();
            $nothing['station_id'] = 0;
            $nothing['station_name'] = '';
            $nothing['loc_city'] = '';
            $nothing['loc_country_code'] = $ccs;
            $nothing['loc_timezone'] = '';
            $nothing['loc_latitude'] = '';
            $nothing['loc_longitude'] = '';
            $nothing['loc_altitude'] = '';
            return $nothing;
        }
        else {
            global $wpdb;
            $table_name = $wpdb->prefix . self::live_weather_station_owm_stations_table();
            $sql = "SELECT * FROM " . $table_name . " WHERE station_id=" . $station_id;
            try {
                $query = (array)$wpdb->get_results($sql);
                $query_a = (array)$query;
                $result = array();
                foreach ($query_a as $val) {
                    $result[] = (array)$val;
                }
                return $result[0];
            } catch (Exception $ex) {
                return array();
            }
        }
    }

    /**
     * Get an OpenWeatherMap station list.
     *
     * @param   array   $station_id     The array of stations id.
     * @return  array   An array containing the stations details.
     * @since    2.0.0
     */
    protected function get_owm_stations($station_id) {
        if (count($station_id) > 0) {
            global $wpdb;
            $table_name = $wpdb->prefix . self::live_weather_station_owm_stations_table();
            $sql = "SELECT * FROM " . $table_name . " WHERE station_id IN (".implode(',', $station_id).')';
            try {
                $query = (array)$wpdb->get_results($sql);
                $query_a = (array)$query;
                $result = array();
                foreach ($query_a as $val) {
                    $result[] = (array)$val;
                }
                return $result;
            } catch (Exception $ex) {
                return array();
            }
        }
        else {
            return array();
        }
    }

    /**
     * Get a list of all OpenWeatherMap stations.
     *
     * @return  array   An array containing the details of all stations.
     * @since    2.0.0
     */
    protected function get_all_owm_stations() {
        global $wpdb;
        $table_name = $wpdb->prefix . self::live_weather_station_owm_stations_table();
        $sql = "SELECT * FROM ".$table_name;
        try {
            $query = (array)$wpdb->get_results($sql);
            $query_a = (array)$query;
            $result = array();
            foreach ($query_a as $val) {
                $result[] = (array)$val;
            }
            return $result;
        } catch (Exception $ex) {
            return array();
        }
    }

    /**
     * Get outdoor datas.
     *
     * @param   string      $device_id                  The device ID.
     * @param   boolean     $obsolescence_filtering     Don't return obsolete data.
     * @return  array   An array containing the outdoor datas.
     * @since    1.0.0
     * @access   protected
     */
    protected function get_outdoor_datas($device_id, $obsolescence_filtering=false) {
        global $wpdb;
        $table_name = $wpdb->prefix.self::live_weather_station_datas_table();
        $sql = "SELECT * FROM ".$table_name. " WHERE device_id='".$device_id."' AND (module_type='NAMain' OR module_type='NAComputed' OR module_type='NACurrent' OR module_type='NAModule1' OR module_type='NAModule2' OR module_type='NAModule3') ORDER BY module_id ASC" ;
        try {
            $query = (array)$wpdb->get_results($sql);
            $query_a = (array)$query;
            $result = array();
            foreach ($query_a as $val) {
                $result[] = (array)$val;
            }
            return ($obsolescence_filtering ? $this->obsolescence_filtering($result) : $result);
        }
        catch(Exception $ex) {
            return array('condition' => array('value' => 2, 'message' => __('Database contains inconsistent datas', 'live-weather-station')));
        }
    }

    /**
     * Get ephemeris datas.
     *
     * @param   string  $device_id  The device ID.
     * @return  array   An array containing the ephemeris datas.
     * @since    1.0.0
     * @access   protected
     */
    protected function get_ephemeris_datas($device_id) {
        global $wpdb;
        $table_name = $wpdb->prefix.self::live_weather_station_datas_table();
        $sql = "SELECT * FROM ".$table_name. " WHERE device_id='".$device_id."' AND (module_type='NAMain' OR module_type='NAEphemer') ORDER BY module_id ASC" ;
        try {
            $query = (array)$wpdb->get_results($sql);
            $query_a = (array)$query;
            $result = array();
            foreach ($query_a as $val) {
                $result[] = (array)$val;
            }
            return $result;
        }
        catch(Exception $ex) {
            return array('condition' => array('value' => 2, 'message' => __('Database contains inconsistent datas', 'live-weather-station')));
        }
    }

    /**
     * Get all datas for a single station.
     *
     * @param   string  $device_id  The device ID.
     * @param   boolean     $obsolescence_filtering     Don't return obsolete data.
     * @return array An array containing all the datas.
     * @since    1.0.0
     * @access   protected
     */
    protected function get_all_datas($device_id, $obsolescence_filtering=false) {
        global $wpdb;
        $table_name = $wpdb->prefix.self::live_weather_station_datas_table();
        $sql = "SELECT * FROM ".$table_name. " WHERE device_id='".$device_id."' ORDER BY module_id ASC" ;
        try {
            $query = (array)$wpdb->get_results($sql);
            $query_a = (array)$query;
            $result = array();
            foreach ($query_a as $val) {
                $result[] = (array)$val;
            }
            return ($obsolescence_filtering ? $this->obsolescence_filtering($result) : $result);
        }
        catch(Exception $ex) {
            return array('condition' => array('value' => 2, 'message' => __('Database contains inconsistent datas', 'live-weather-station')));
        }
    }

    /**
     * Get the name of a station.
     *
     * @param   string  $device_id  The device ID.
     * @return  string  The name of the station.
     * @since    1.0.0
     * @access   protected
     */
    protected function get_station_name($device_id) {
        global $wpdb;
        $table_name = $wpdb->prefix.self::live_weather_station_datas_table();
        $sql = "SELECT DISTINCT device_name FROM ".$table_name. " WHERE device_id='".$device_id."'" ;
        try {
            $query = (array)$wpdb->get_results($sql);
            $query_a = (array)$query;
            $query_t = (array)$query_a[0];
            $result = $query_t['device_name'];
            return $result;
        }
        catch(Exception $ex) {
            return array('condition' => array('value' => 2, 'message' => __('Database contains inconsistent datas', 'live-weather-station')));
        }
    }

    /**
     * Get all datas for a single module.
     *
     * @param   string  $module_id  The module ID.
     * @param   boolean     $obsolescence_filtering     Don't return obsolete data.
     * @return array An array containing all the datas.
     * @since    1.0.0
     * @access   protected
     */
    protected function get_module_datas($module_id, $obsolescence_filtering=false) {
        global $wpdb;
        $table_name = $wpdb->prefix.self::live_weather_station_datas_table();
        $sql = "SELECT * FROM ".$table_name. " WHERE module_id='".$module_id."'" ;
        try {
            $query = (array)$wpdb->get_results($sql);
            $query_a = (array)$query;
            $result = array();
            foreach ($query_a as $val) {
                $result[] = (array)$val;
            }
            return ($obsolescence_filtering? $this->obsolescence_filtering($result) : $result);
        }
        catch(Exception $ex) {
            return array('condition' => array('value' => 2, 'message' => __('Database contains inconsistent datas', 'live-weather-station')));
        }
    }

    /**
     * Get specific data.
     *
     * @param   array   $attributes  An array representing the query.
     * @param   boolean     $obsolescence_filtering     Don't return obsolete data.
     * @return array An array containing all the datas.
     * @since    1.0.0
     * @access   protected
     */
    protected function get_specific_datas($attributes, $obsolescence_filtering=false) {
        global $wpdb;
        $sub_attributes = array();
        switch ($attributes['measure_type']) {
            case 'dew_point':
                $sub_attributes[] = 'dew_point';
                $sub_attributes[] = 'temperature_ref';
                break;
            case 'frost_point':
                $sub_attributes[] = 'frost_point';
                $sub_attributes[] = 'temperature_ref';
                break;
            case 'heat_index':
                $sub_attributes[] = 'heat_index';
                $sub_attributes[] = 'dew_point';
                $sub_attributes[] = 'temperature_ref';
                $sub_attributes[] = 'humidity_ref';
                break;
            case 'humidex':
                $sub_attributes[] = 'humidex';
                $sub_attributes[] = 'dew_point';
                $sub_attributes[] = 'temperature_ref';
                $sub_attributes[] = 'humidity_ref';
                break;
            case 'wind_chill':
                $sub_attributes[] = 'wind_chill';
                $sub_attributes[] = 'temperature_ref';
                break;
            default:
                $sub_attributes[] = $attributes['measure_type'];
        }
        $measures = "";
        if (count($sub_attributes)>0) {
            $i = 0;
            foreach ($sub_attributes as $att) {
                $measures = $measures . ($i!=0?" OR ":"")."measure_type='" . $att . "'";
                $i++;
            }
        }
        $table_name = $wpdb->prefix . self::live_weather_station_datas_table();
        $sql = "SELECT " . $attributes['element'] . ", module_type" . ($attributes['element']!="measure_type"?", measure_type":"") . " FROM " . $table_name . " WHERE device_id='" . $attributes['device_id'] . "' AND module_id='" . $attributes['module_id'] . "' AND (" . $measures . ")";
        $result = array();
        try {
            $query = (array)$wpdb->get_results($sql);
            $i = 0;
            foreach ($query as $q) {
                $tmp = (array)$q;
                $result['result'][$tmp['measure_type']] = $tmp[$attributes['element']];
                if ($attributes['measure_type']==$tmp['measure_type']) {
                    $result['module_type'] = $tmp['module_type'];
                }
                $i++;
            }
            return ($obsolescence_filtering ? $this->obsolescence_filtering($result) : $result);
        }
        catch (Exception $ex) {
            return array();
        }
    }

    /**
     * Get stations list with latitude and longitude set.
     *
     * @return  array   An array containing the located stations.
     * @since    2.0.0
     * @access   protected
     */
    protected function get_located_stations_list() {
        global $wpdb;
        $table_name = $wpdb->prefix.self::live_weather_station_datas_table();
        $sql = "SELECT DISTINCT device_id, device_name FROM ".$table_name ;
        try {
            $query = (array)$wpdb->get_results($sql);
            $query_a = (array)$query;
            $result = array();
            foreach ($query_a as $val) {
                $result[] = (array)$val;
            }
        }
        catch(Exception $ex) {
            $result = array() ;
        }
        $count = count ($result);
        $rq = '';
        foreach ($result as $res) {
            $count = $count - 1;
            $rq = $rq . "device_id='".$res['device_id']."'" ;
            if ($count > 0) {
                $rq = $rq . ' OR ';
            }
        }
        if ($rq != '') {
            $rq = " AND (".$rq.")" ;
        }
        $sql = "SELECT device_id, device_name, measure_type, measure_value FROM ".$table_name." WHERE (module_type='NAMain') AND (measure_type='loc_latitude' OR measure_type='loc_longitude' OR measure_type='loc_timezone')".$rq ;
        try {
            $query = (array)$wpdb->get_results($sql);
            $query_a = (array)$query;
            $result = array();
            foreach ($query_a as $val) {
                $result[] = (array)$val;
            }
        }
        catch(Exception $ex) {
            $result = array() ;
        }
        $return = array();
        foreach ($result as $res) {
            $return[$res['device_id']]['device_name'] = $res['device_name'] ;
            $return[$res['device_id']][$res['measure_type']] = $res['measure_value'] ;
        }
        return $return;
    }

    /**
     * Get stations list with reference values (to compute dew point, wind chill,...).
     *
     * @return  array   An array containing the stations with reference values.
     * @since    2.0.0
     */
    private function get_reference_values() {
        global $wpdb;
        $table_name = $wpdb->prefix.self::live_weather_station_datas_table();
        $sql = "SELECT device_id, device_name, module_type, measure_timestamp, measure_type, measure_value FROM ".$table_name." WHERE (module_type='NAModule1' OR module_type='NAModule2' OR module_type='NACurrent') AND (measure_type='temperature' OR measure_type='humidity' OR measure_type='windstrength')" ;
        try {
            $query = (array)$wpdb->get_results($sql);
            $query_a = (array)$query;
            $result = array();
            foreach ($query_a as $val) {
                $result[] = (array)$val;
            }
        }
        catch(Exception $ex) {
            $result = array() ;
        }
        $return = array();
        foreach ($result as $res) {
            $return[$res['device_id']]['device_name'] = $res['device_name'] ;
            $return[$res['device_id']][$res['measure_type']][$res['module_type']]['value'] = $res['measure_value'] ;
            $return[$res['device_id']][$res['measure_type']][$res['module_type']]['timestamp'] = $res['measure_timestamp'] ;
        }
        $result = array();
        foreach ($return as $device_id => $device) {
            foreach ($device as $measure_type => $measure) {
                if (is_array($measure)) {
                    $value = -9999;
                    foreach ($measure as $module_type => $module) {
                        $value = $module['value'];
                        $diff = round ((abs( strtotime(get_date_from_gmt(date('Y-m-d H:i:s'))) - strtotime(get_date_from_gmt($module['timestamp']))))/60);
                        $ts = $module['timestamp'];
                        if ($measure_type == 'temperature' && $module_type == 'NAModule1' && ($diff < $this->delta_time)) {
                            break;
                        }
                        if ($measure_type == 'humidity' && $module_type == 'NAModule1' && ($diff < $this->delta_time)) {
                            break;
                        }
                        if ($measure_type == 'windstrength' && $module_type == 'NAModule2' && ($diff < $this->delta_time)) {
                            break;
                        }
                    }
                    $result[$device_id]['name'] = $device['device_name'];
                    $result[$device_id][$measure_type] = $value;
                }
            }
        }
        return $result;
    }
}