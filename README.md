RainEE
===============
ExpressionEngine plugin that returns the forecast for a given zip code, address, or WOEID (Where On Earth IDentifier).

*US zip codes and address will return a five day forecast as Yahoo provides them through an XML feed.*

Parameters
===============
zip_code				 - (string)		 - The US zip code
address					 - (string)		 - The address US or international
woeid					 - (string)		 - The WOEID (Where On Earth IDentifier)
*One of these three is required*

Tags
===============
{rne_as_of}				 - (string)		 - When the forecast was last updated.

{rne_city}				 - (string)		 - The city the forecast is for.
{rne_country}			 - (string)		 - Country of the forecast.
{rne_region}			 - (string)		 - The region, i.e. state, provence.

{rne_units_temp}		 - (string)		 - The units for temperature.
{rne_units_distance}	 - (string)		 - The units for distance, i.e. visibility.
{rne_units_speed}		 - (string)		 - Units for speed, metric or standard.
{rne_units_pressure}	 - (string)		 - Untis for pressure.

{rne_temp}				 - (int)		 - Temparature as an interger with no degrees or units.
{rne_weather_code}		 - (int)		 - The Yahoo weather code that corresponds to the current conditions.
{rne_conditions}		 - (string)		 - Current weather conditions

{rne_sunrise}			 - (string)		 - When the will rise on the current day.
{rne_sunset}			 - (string)		 - The time of sunset today.

{rne_humidity}			 - (int)		 - Humidity as a percent.
{rne_pressure}			 - (int)		 - The current atmospheric pressure.
{rne_rising}			 - (int)		 - The rate at which air is rising.
{rne_visibility}		 - (int)		 - Visibility in distance units.

{rne_wind_chill}		 - (int)		 - The perceived tempature with wind.
{rne_wind_direction}	 - (int)		 - The direction wind is coming from.
{rne_wind_speed}		 - (int)		 - Wind speed in speed units.

{rne_forecast}			 - (tag pair)	 - Tag pair of upcoming forecast
	{rne_day}			 - (string)		 - Three day abbreviation for the day of the forecast.
	{rne_date}			 - (string)		 - The date the forecast is for.
	{rne_high}			 - (int)		 - The anticpated high tempature.
	{rne_low}			 - (int)		 - Low temperature for the day.
	{rne_weather_code}	 - (int)		 - The Yahoo weather code.
	{rne_conditions}	 - (string)		 - The weather condition for the given day.

Example
===============
	{exp:rainee address="124 12th Avenue South, Suite 510 Nashville, TN 37203"}
		
		<h1>Forecast for {rne_city}, {rne_region} {rne_country}</h1>

		<p><small>Updated: {rne_as_of}</small></p>

		<section class="weather-today weather-{rne_weather_code}">

			<h2>Today</h2>

			<p>
				{rne_conditions} and {rne_temp}&deg; {rne_units_temp}. Feels like {rne_wind_chill}&deg; {rne_units_temp}.<br />
				{if rne_wind_speed > 0}Winds at {rne_wind_speed}{rne_units_speed} from the {if rne_wind_direction > 337.5}north{if:elseif rne_wind_direction > 292.5}northwest{if:elseif rne_wind_direction > 247.5}west{if:elseif rne_wind_direction > 202.5}southwest{if:elseif rne_wind_direction > 157.5}south{if:elseif rne_wind_direction > 112.5}southeast{if:elseif rne_wind_direction > 67.5}east{if:elseif rne_wind_direction > 22.5}northeast{if:else}north{/if}.{if:else}No wind.{/if}
			</p>

			<ul class="atmosphere">

				<li><b>Humidity:</b> {rne_humidity}%</li>

				<li><b>Pressure:</b> {rne_pressure} {rne_units_pressure}</li>

				<li><b>Rising:</b> {rne_rising}</li>

				<li><b>Visibility:</b> {rne_visibility} {rne_units_distance}</li>

			</ul>

			<ul class="astronomy">

				<li><b>Sunrise:</b> {rne_sunrise}</li>

				<li><b>Sunset:</b> {rne_sunset}</li>

			</ul>

		</section>

		{rne_forecast}

			<div class="weather-forecast weather-{rne_weather_code}">

				<h3>{rne_day} - {rne_date}</h3>

				<p>
					{rne_conditions} width a high of {rne_high}&deg; {rne_units_temp} and a low of {rne_low}&deg; {rne_units_temp}.
				</p>

			</div>

		{/rne_forecast}

	{/exp:rainee}