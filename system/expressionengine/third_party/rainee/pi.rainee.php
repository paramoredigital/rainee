<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * RainEE Plugin
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Plugin
 * @author		Chris Lock
 * @link		http://paramore.is/chris
 */

$plugin_info = array(
	'pi_name'		=> 'RainEE',
	'pi_version'	=> '1.1',
	'pi_author'		=> 'Chris Lock',
	'pi_author_url'	=> 'http://paramore.is/chris',
	'pi_description'=> 'Returns forecast for a given zip code, address, or WOEID',
	'pi_usage'		=> Rainee::usage()
);


class Rainee {

	/**
	 * Constructor
	 * Sets $rainee_tags as template tags from Yahoo Weather
	 * 
	 * As of timestamp
	 *	- (string) rne_as_of
	 * 
	 * Location
	 *	- (string) rne_city
	 *	- (string) rne_country
	 *	- (string) rne_region
	 * 
	 * Units
	 *	- (string) rne_units_temp
	 *	- (string) rne_units_distance
	 *	- (string) rne_units_speed
	 *	- (string) rne_units_pressure
	 * 
	 * Current conditions
	 *	- (int) rne_temp
	 *	- (int) rne_weather_code
	 *	- (string) rne_conditions
	 * 
	 * Sunrise & sunset
	 *	- (string) rne_sunrise
	 *	- (string) rne_sunset
	 * 
	 * Atmosphere
	 *	- (int) rne_humidity
	 *	- (int) rne_pressure
	 *	- (int) rne_rising
	 *	- (int) rne_visibility
	 * 
	 * Wind
	 *	- (int) rne_wind_chill
	 *	- (int) rne_wind_direction
	 *	- (int) rne_wind_speed
	 * 
	 * Forecast
	 *	- (array) rne_forecast
	 *		- array
	 *			- (string) rne_day
	 *			- (string) rne_date
	 *			- (int) rne_high
	 *			- (int) rne_low
	 *			- (int) rne_weather_code
	 *			- (string) rne_conditions
	 * 
	 * @author Chris Lock
	 */
	public function __construct() {

		$this->EE =& get_instance();

		// Get parameters
		$zip_code = $this->EE->TMPL->fetch_param('zip_code');
		$address = $this->EE->TMPL->fetch_param('address');
		$woeid = $this->EE->TMPL->fetch_param('woeid');

		// Ideal parameter order
		if ($zip_code) {

			$weather_data = $this->_get_weather_data($zip_code);

		} elseif ($address) {

			$location_zip = $this->_get_zip_code($address);

			// If no zip code
			// get WOEID and set location_id_type
			$location_id = ($location_zip === false) ? $this->_get_woeid($address) : $location_zip;
			$location_id_type = ($location_zip === false) ? 'woeid' : '';

			$weather_data = $this->_get_weather_data($location_id, $location_id_type);

		} elseif ($woeid) {

			$weather_data = $this->_get_weather_data($woeid, 'woeid');

		}

		// All three parameters failed to produce results
		// or no parameters
		if (!isset($weather_data)) {

			$this->return_data = $this->EE->TMPL->no_results();

			return;

		}

		$rainee_tags[] = self::_map_weather_data($weather_data);

		$this->return_data = $this->EE->TMPL->parse_variables($this->EE->TMPL->tagdata, $rainee_tags);

	}

	/**
	 * Returns zip code for a given address
	 * @param string $location The address for the zip code
	 * @return mixed $zip: (string) US zip code | (bool false) failure
	 * @author Chris Lock
	*/
	private function _get_zip_code($address) {

		// Must have a location
		if (!$address) return false;

		$zip_code_query = "
			SELECT *
			FROM geo.places
			WHERE text='".$address."'
		";

		$this->EE->load->library('rainee_yql_library');

		$zip_code_result_array = $this->EE
			->rainee_yql_library
			->run_query($zip_code_query);

		// Debug
		// die(var_dump($zip_code_result_array));

		// Return $zip or false
		return (isset($zip_code_result_array['place']['postal']['type'])
			AND $zip_code_result_array['place']['postal']['type'] == 'Zip Code'
			AND isset($zip_code_result_array['place']['postal']['content']))
			? $zip_code_result_array['place']['postal']['content']
			: false;

	}

	/**
	 * Returns WOEID for a given location
	 * @param string $location The location for the WOEID
	 * @return mixed $woeid: (string) weather data from YQL | (bool false) failure
	 * @author Chris Lock
	*/
	private function _get_woeid($location) {

		// Must have a location
		if (!$location) return false;

		$woeid_query = "
			SELECT *
			FROM geo.places
			WHERE text='".$location."'
		";

		$this->EE->load->library('rainee_yql_library');

		$woeid_result_array = $this->EE
			->rainee_yql_library
			->run_query($woeid_query);

		// Debug
		// die(var_dump($woeid_result_array));

		// Return $woeid or false
		return (isset($woeid_result_array['place']['woeid']))
			? $woeid_result_array['place']['woeid']
			: false;

	}

	/**
	 * Retrieves weather data from YQL for a given zip code or WOEID
	 * @param string $location_id The WOEID or zip code for the weather location
	 * @param string $location_id_type (optional) The $location_id type, assumed zip code
	 * @return mixed $weather_data: (array) weather data from YQL | (bool false) failure
	 * @author Chris Lock
	*/
	private function _get_weather_data($location_id, $location_id_type = 'zip code') {

		// Must have a location_id
		if (!$location_id) return false;

		// If it's not a WOEID, must be a zip code
		$location_id_db_column = ($location_id_type == 'woeid') ? 'woeid' : 'location';

		$weather_query = "
			SELECT *
			FROM weather.forecast
			WHERE ".$location_id_db_column."='".$location_id."'
		";

		// Debug
		// die($weather_query);
		
		$this->EE->load->library('rainee_yql_library');

		$weather_data = $this->EE
			->rainee_yql_library
			->run_query($weather_query);

		// Debug
		// die(var_dump($weather_data));

		// Get 5 day forecast if a using a zip code
		if ($location_id_type = 'zip code'
			AND isset($weather_data['channel']['item']['forecast'])) {

			$five_day_weather_data = $this->_get_five_day_forecast($location_id);

			if ($five_day_weather_data)
				$weather_data['channel']['item']['forecast'] = $five_day_weather_data;

		}

		// Debug
		// die(var_dump($weather_data));

		// Return weather_data or false
		return (isset($weather_data['channel']))
			? $weather_data['channel']
			: false;

	}

	/**
	 * Retrieves five day forecast from YQL for a given zip code
	 * @param string $location_id The zip code for the weather location
	 * @return mixed $weather_data: (array) weather data from YQL | (bool false) failure
	 * @author Chris Lock
	*/
	private function _get_five_day_forecast($location_id) {

		// Must have a location_id
		if (!$location_id) return false;

		$five_day_weather_query = "
			SELECT *
			FROM rss
			WHERE url='http://xml.weather.yahoo.com/forecastrss/".$location_id.".xml'
		";
		
		$this->EE->load->library('rainee_yql_library');

		$five_day_weather_data = $this->EE
			->rainee_yql_library
			->run_query($five_day_weather_query);

		// Debug
		// die(var_dump($five_day_weather_data));

		// Return five_day_weather_data or false
		return (isset($five_day_weather_data['item']['forecast']))
			? $five_day_weather_data['item']['forecast']
			: false;

	}

	/**
	 * Maps YQL weather data to a array for EE tags
	 * @param array $weather_data Weather data from YQL's weather.forecast table
	 * @return array $rainee_tags Weatehr data in an array formatted for EE
	 * @author Chris Lock
	*/
	private static function _map_weather_data($weather_data) {

		// Since we're no directly mapping from YQL
		// this seems like the best solution
		$rainee_tags = array();

		// As of time stamp
		$rainee_tags['rne_as_of'] = (isset($weather_data['lastBuildDate']))
			? $weather_data['lastBuildDate']
			: null;

		// Location
		$rainee_tags['rne_city'] = (isset($weather_data['location']['city']))
			? $weather_data['location']['city']
			: null;
		$rainee_tags['rne_country'] = (isset($weather_data['location']['country']))
			? $weather_data['location']['country']
			: null;
		$rainee_tags['rne_region'] = (isset($weather_data['location']['region']))
			? $weather_data['location']['region']
			: null;

		// Units
		$rainee_tags['rne_units_temp'] = (isset($weather_data['units']['temperature']))
			? $weather_data['units']['temperature']
			: null;
		$rainee_tags['rne_units_distance'] = (isset($weather_data['units']['distance']))
			? $weather_data['units']['distance']
			: null;
		$rainee_tags['rne_units_speed'] = (isset($weather_data['units']['speed']))
			? $weather_data['units']['speed']
			: null;
		$rainee_tags['rne_units_pressure'] = (isset($weather_data['units']['pressure']))
			? $weather_data['units']['pressure']
			: null;

		// Current conditions
		$rainee_tags['rne_temp'] = (isset($weather_data['item']['condition']['temp']))
			? $weather_data['item']['condition']['temp']
			: 0;
		$rainee_tags['rne_weather_code'] = (isset($weather_data['item']['condition']['code']))
			? $weather_data['item']['condition']['code']
			: 0;
		$rainee_tags['rne_conditions'] = (isset($weather_data['item']['condition']['text']))
			? $weather_data['item']['condition']['text']
			: null;

		// Sunrise & sunset
		$rainee_tags['rne_sunrise'] = (isset($weather_data['astronomy']['sunrise']))
			? $weather_data['astronomy']['sunrise']
			: null;
		$rainee_tags['rne_sunset'] = (isset($weather_data['astronomy']['sunset']))
			? $weather_data['astronomy']['sunset']
			: null;

		// Atmosphere
		$rainee_tags['rne_humidity'] = (isset($weather_data['atmosphere']['humidity']))
			? $weather_data['atmosphere']['humidity']
			: 0;
		$rainee_tags['rne_pressure'] = (isset($weather_data['atmosphere']['pressure']))
			? $weather_data['atmosphere']['pressure']
			: 0;
		$rainee_tags['rne_rising'] = (isset($weather_data['atmosphere']['rising']))
			? $weather_data['atmosphere']['rising']
			: 0;
		$rainee_tags['rne_visibility'] = (isset($weather_data['atmosphere']['visibility']))
			? $weather_data['atmosphere']['visibility']
			: 0;

		// Wind
		$rainee_tags['rne_wind_chill'] = (isset($weather_data['wind']['chill']))
			? $weather_data['wind']['chill']
			: 0;
		$rainee_tags['rne_wind_direction'] = (isset($weather_data['wind']['direction']))
			? $weather_data['wind']['direction']
			: 0;
		$rainee_tags['rne_wind_speed'] = (isset($weather_data['wind']['speed']))
			? $weather_data['wind']['speed']
			: 0;

		// Forecast
		$rainee_forecast = array();

		if (isset($weather_data['item']['forecast'])) {

			foreach ($weather_data['item']['forecast'] as $forecast_index => $forecast_array) {

				$rainee_forecast[$forecast_index]['rne_day'] = (isset($forecast_array['day']))
					? $forecast_array['day']
					: null;
				$rainee_forecast[$forecast_index]['rne_date'] = (isset($forecast_array['date']))
					? $forecast_array['date']
					: null;
				$rainee_forecast[$forecast_index]['rne_high'] = (isset($forecast_array['high']))
					? $forecast_array['high']
					: 0;
				$rainee_forecast[$forecast_index]['rne_low'] = (isset($forecast_array['low']))
					? $forecast_array['low']
					: 0;
				$rainee_forecast[$forecast_index]['rne_weather_code'] = (isset($forecast_array['code']))
					? $forecast_array['code']
					: 0;
				$rainee_forecast[$forecast_index]['rne_conditions'] = (isset($forecast_array['text']))
					? $forecast_array['text']
					: null;

			}

		}

		$rainee_tags['rne_forecast'] = $rainee_forecast;

		// Debug
		// die(var_dump($rainee_tags));

		return $rainee_tags;

	}

	/**
	 * Plugin Usage
	 */
	public static function usage() {

		ob_start();

		$dir = dirname(__file__);
		$read_me = file_get_contents($dir.'/README.md');

		echo $read_me;
		
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;

	}
}


/* End of file pi.rainee.php */
/* Location: /system/expressionengine/third_party/rainee/pi.rainee.php */